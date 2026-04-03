@include('ssh.os.apt-retry')

vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get install redis-server -y

sudo sed -i 's/bind 127.0.0.1 ::1/bind 0.0.0.0/g' /etc/redis/redis.conf

sudo systemctl enable redis-server

sudo systemctl start redis-server
