How to install Bilemo :

1. Clone the project on the folder you want the project to be with the command "git clone https://github.com/Steelwix/p7bilemo"

for Docker {
2. Download the file named "latest.tar" and place it anywhere you can find it. It is the database for Docker. Install docker.

3. In the terminal, use the command "docker import /path/to/latest.tar"

4. Back to Docker, run the p7bilemo container. You can edit settings in docker-compose files to fit your settings.
}

for any other {
2. In the .env file, add " # " to the postgresql line, and remove the " # " to the mysql line and adapt your datas to the line.
3. Load the datas in your database with the command "symfony console doctrine:fixtures:load"
}
5. Run the project with the command "symfony serve -d --no-tls, you may need to replace the IP address with "localhost." "

6. You can get the documentation with the api.html file, or with GET /api/doc. You can log with POST /api/login_check.

7. SUPER_ADMIN account = {
    "username": "Superadmin",
    "password": "motdepasse"
}

    ADMIN account : {
    "username": "AdminOrange",
    "password": "motdepasse"
}
    USER account : {
    "username": "Virgil",
    "password": "devilmaycry"
}

