@include('ssh.os.apt-retry')

if command -v mise &> /dev/null; then
    echo "Mise is already installed"
    mise --version
    exit 0
fi

vito_retry_apt sudo apt update -y && vito_retry_apt sudo apt install -y curl
sudo install -dm 755 /etc/apt/keyrings
curl -fsSL https://mise.jdx.dev/gpg-key.pub | sudo tee /etc/apt/keyrings/mise-archive-keyring.pub 1> /dev/null
echo "deb [signed-by=/etc/apt/keyrings/mise-archive-keyring.pub arch=amd64] https://mise.jdx.dev/deb stable main" | sudo tee /etc/apt/sources.list.d/mise.list
vito_retry_apt sudo apt update
vito_retry_apt sudo apt install -y mise

mise --version
echo "Mise installed successfully"
