#!/usr/bin/env bash

set -Eeuo pipefail

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
HOT_FILE="${ROOT_DIR}/public/hot"
WS_PORT="8085"

resolve_ws_port() {
    if [[ -f "${ROOT_DIR}/.env" ]]; then
        local configured_port
        configured_port="$(
            sed -nE 's/^WS_PORT="?([^"#]+)"?.*/\1/p' "${ROOT_DIR}/.env" \
                | tail -n 1
        )"

        if [[ -n "${configured_port}" ]]; then
            WS_PORT="${configured_port}"
        fi
    fi
}

terminate_pid() {
    local pid="$1"

    kill "${pid}" 2>/dev/null || sudo kill "${pid}" 2>/dev/null || true
}

force_terminate_pid() {
    local pid="$1"

    kill -9 "${pid}" 2>/dev/null || sudo kill -9 "${pid}" 2>/dev/null || true
}

stop_supervisor_programs() {
    sudo supervisorctl stop websocket >/dev/null 2>&1 &
    sudo supervisorctl stop worker:* >/dev/null 2>&1 &
    sleep 1
}

is_repo_managed_command() {
    local cmdline="$1"

    case "${cmdline}" in
        *"${ROOT_DIR}/artisan ws:serve"* \
        |*"${ROOT_DIR}/artisan horizon"* \
        |*"${ROOT_DIR}/node_modules/.bin/vite"* \
        |*"${ROOT_DIR}/node_modules/.bin/concurrently"* \
        |*"artisan horizon:supervisor"* \
        |*"artisan horizon:work"* \
        |*"artisan horizon"* \
        |*"artisan schedule:work"* \
        |*"artisan pail --timeout=0"* \
        |*"artisan ws:serve"* \
        |*"concurrently --kill-others-on-fail"* \
        |*"sh -c vite"* \
        |*"npm run dev"*)
            return 0
            ;;
        *)
            return 1
            ;;
    esac
}

find_repo_processes() {
    local pid cwd cmdline

    while read -r pid; do
        [[ -n "${pid}" ]] || continue
        [[ "${pid}" != "$$" ]] || continue
        [[ "${pid}" != "${PPID:-0}" ]] || continue

        cwd="$(readlink -f "/proc/${pid}/cwd" 2>/dev/null || true)"
        cmdline="$(cat "/proc/${pid}/cmdline" 2>/dev/null | tr '\0' ' ' || true)"
        [[ -n "${cmdline}" ]] || continue

        if is_repo_managed_command "${cmdline}" && [[ "${cmdline}" == *"${ROOT_DIR}"* || ( -n "${cwd}" && "${cwd}" == "${ROOT_DIR}"* ) ]]; then
            echo "${pid}"
        fi
    done < <(ps -eo pid=)
}

find_websocket_listener_pids() {
    ss -ltnp 2>/dev/null \
        | rg "127\\.0\\.0\\.1:${WS_PORT}|0\\.0\\.0\\.0:${WS_PORT}|\\[::\\]:${WS_PORT}" -n -S \
        | rg -o 'pid=[0-9]+' \
        | cut -d= -f2 \
        | sort -u
}

stop_processes() {
    resolve_ws_port
    stop_supervisor_programs

    mapfile -t repo_pids < <(find_repo_processes | sort -u)
    mapfile -t websocket_pids < <(find_websocket_listener_pids)

    if (( ${#websocket_pids[@]} > 0 )); then
        repo_pids+=("${websocket_pids[@]}")
    fi

    if (( ${#repo_pids[@]} > 0 )); then
        mapfile -t repo_pids < <(printf '%s\n' "${repo_pids[@]}" | sort -u)
    fi

    if (( ${#repo_pids[@]} > 0 )); then
        for pid in "${repo_pids[@]}"; do
            terminate_pid "${pid}"
        done

        sleep 1

        for pid in "${repo_pids[@]}"; do
            if kill -0 "${pid}" 2>/dev/null; then
                force_terminate_pid "${pid}"
            fi
        done
    fi

    rm -f "${HOT_FILE}"
}

start_processes() {
    cd "${ROOT_DIR}"

    stop_processes

    sudo systemctl restart nginx php8.4-fpm

    php artisan migrate --force --no-interaction

    trap stop_processes EXIT INT TERM

    npx concurrently \
        --kill-others-on-fail \
        -c "#c4b5fd,#fb7185,#fdba74,#fabb74,#86efac" \
        "php artisan horizon" \
        "php artisan schedule:work" \
        "php artisan pail --timeout=0" \
        "npm run dev" \
        "php artisan ws:serve" \
        --names=horizon,schedule,logs,vite,websocket
}

case "${1:-start}" in
    start)
        start_processes
        ;;
    stop)
        stop_processes
        ;;
    *)
        echo "Usage: ${0} [start|stop]" >&2
        exit 1
        ;;
esac
