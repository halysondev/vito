@include('ssh.os.apt-retry')

wget https://downloads.mariadb.com/MariaDB/mariadb_repo_setup

chmod +x mariadb_repo_setup

vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive ./mariadb_repo_setup \
    --mariadb-server-version="mariadb-10.11"

vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get update

vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get install mariadb-server mariadb-backup -y

sudo systemctl unmask mysql.service

sudo service mysql start
