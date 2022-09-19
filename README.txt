How to install Bilemo :

1. clone the project on the folder you want the project to be with the command "git clone https://github.com/Steelwix/p7bilemo"

2. the "latest.tar" is the database for Docker. Install docker.

3. In the terminal, use the command "docker import /path/to/latest.tar" (If you are already in the folder, the command should be "docker import latest.tar")

4. Back to Docker, run the p7bilemo container. You can edit settings in docker-compose files to fit your settings.

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

