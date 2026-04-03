@include('ssh.os.apt-retry')

# Update package lists
vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get update -y

# Remove unnecessary dependencies
vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get autoremove --purge -y

# Clear package cache
vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get clean -y

# Remove old configuration files
vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get purge -y $(dpkg -l | grep '^rc' | awk '{print $2}')

# Clear temporary files
sudo rm -rf /tmp/*

# Clear journal logs
vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive journalctl --vacuum-time=1d

echo "Cleanup completed."
