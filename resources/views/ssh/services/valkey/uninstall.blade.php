@include('ssh.os.apt-retry')

sudo systemctl stop valkey-server

vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get remove valkey-server -y

sudo rm -rf /etc/valkey
sudo rm -rf /var/lib/valkey
sudo rm -rf /var/log/valkey
sudo rm -rf /var/run/valkey

vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive sudo apt-get autoremove -y

vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive sudo apt-get autoclean -y
