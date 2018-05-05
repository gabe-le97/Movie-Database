# Movie-Database
Uses MySQL, PHP, twig files & the Silex Web Framework to develop a web-based database application

The Movie Database has been created and populated using phpMyAdmin. 
The Database was queried by manually entering data and importing a sql file.

***

Interface was constructed so users could:
* Search the Database
* View information about data pulled from the Database
* Log in as an existing user
* Register as a new user

---

__Registration Page__:
* All required fields are entered with the minimum length specified
* Password confirmation
* Check if username is unique and does not already exist
* Return to the homepage after valid registration

__Login Page__:
* Check if login is valid and display an appropriate message
* Set a session variable for the username and user number and redirect to the
homepage if valid

__Query Pages__:
A set of dynamic queries the user can execute on the website that are 
displayed using individual twig files
* Displays all movies (title, year, desc by year) in the Database (limit 15)
* Displays a list of all directors (name, genre, desc by name) in the Database (limit 15)
* Displays the number of actors (genre, no. of actors) in the Database
* Displays a list of all theaters (company name, city, phone number) in the Database
* Displays a list of all actors & the movies they acted in 
(actor name, movie name, movie year, desc by year) in the Database (limit 15)
* Displays a list of the top 10 movies (determined by no. of votes).

***

![home](https://raw.githubusercontent.com/gabe-le97/Movie-Database/master/img/home.png)

![top10](https://raw.githubusercontent.com/gabe-le97/Movie-Database/master/img/top10.png)

![movieDatabase](https://raw.githubusercontent.com/gabe-le97/Movie-Database/master/img/movieDatabase.png)

![movies](https://raw.githubusercontent.com/gabe-le97/Movie-Database/master/img/movies.png)
