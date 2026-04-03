@include('ssh.os.apt-retry')

sudo service mysql stop

vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get purge --remove mysql-server mysql-common mysql-apt-config -y
vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get autoremove --purge -y
vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get autoclean -y

sudo rm -f /etc/apt/trusted.gpg.d/mysql.gpg
sudo rm -f /usr/share/keyrings/mysql-archive-keyring.gpg
sudo rm -rf /var/lib/apt/lists/*mysql*
sudo rm -rf /var/lib/dpkg/info/mysql*
sudo rm -rf /var/cache/apt/archives/*mysql*

sudo rm -rf /etc/mysql
sudo rm -rf /var/lib/mysql
sudo rm -rf /var/log/mysql
sudo rm -rf /var/run/mysqld
sudo rm -rf /var/run/mysqld/mysqld.sock
sudo rm -f /etc/apt/sources.list.d/mysql.list

sudo rm -rf /var/lib/apt/lists/*
vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get update
