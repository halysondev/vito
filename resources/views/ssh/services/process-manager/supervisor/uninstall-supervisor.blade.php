@include('ssh.os.apt-retry')

sudo systemctl stop supervisor

vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get remove supervisor -y

sudo rm -rf /etc/supervisor
sudo rm -rf /var/log/supervisor
sudo rm -rf /var/run/supervisor
sudo rm -rf /var/run/supervisor/supervisor.sock
