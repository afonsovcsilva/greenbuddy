# greenbuddy


# iniciar serviço de docker container myql/phpmyadmin no terminal
docker compose up -d

# iniciar o serviço php na porta 7878 no terminal
php -S localhost:7878

# se der o erro Erro na ligação à base de dados: could not find driver temos de instalar no terminal
sudo apt update
sudo apt install php8.4-mysql -y

# criar um dominio visivel para os serviços
npm install -g ngrok
ngrok http 7878