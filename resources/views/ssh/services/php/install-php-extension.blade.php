@include('ssh.os.apt-retry')

vito_retry_apt sudo apt-get install -y php{{ $version }}-{{ $name }}

sudo service php{{ $version }}-fpm restart

php{{ $version }} -m
