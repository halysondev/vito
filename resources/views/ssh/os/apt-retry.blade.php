vito_retry_apt() {
    local attempts="${VITO_APT_RETRY_ATTEMPTS:-12}"
    local delay="${VITO_APT_RETRY_DELAY:-5}"
    local attempt=1

    while true; do
        if "$@"; then
            return 0
        fi

        local exit_code=$?

        if ! fuser /var/lib/dpkg/lock-frontend >/dev/null 2>&1 \
            && ! fuser /var/lib/dpkg/lock >/dev/null 2>&1 \
            && ! fuser /var/cache/apt/archives/lock >/dev/null 2>&1; then
            return "$exit_code"
        fi

        if [ "$attempt" -ge "$attempts" ]; then
            echo "APT is still locked after ${attempts} attempts." >&2

            return "$exit_code"
        fi

        echo "APT is locked by another process. Retrying in ${delay}s (${attempt}/${attempts})..." >&2
        sleep "$delay"
        attempt=$((attempt + 1))
    done
}
