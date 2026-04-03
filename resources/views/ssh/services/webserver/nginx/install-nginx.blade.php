@include('ssh.os.apt-retry')

vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get install nginx -y

# install certbot
vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get install certbot python3-certbot-nginx -y
