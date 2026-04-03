@include('ssh.os.apt-retry')

vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get clean
vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get update
vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get upgrade -y
vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get autoremove -y
