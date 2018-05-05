<?php
/****************************************************************************

 Movie Website Database Assignment
 Author: Gabe Le
 Due Date: 25, April 2018

This program is designed to demonstrate how to use PhP, MySQL and Silex to 
implement a web application that accesses a database.

Files:  The application is made up of the following files

php: 	index.php - This file has all of the php code in one place.  It is found in 
		the public_html/movie/ directory of the code source.
		
		connect.php - This file contains the specific information for connecting to the
		database.  It is stored two levels above the index.php file to prevent the db 
		password from being viewable.
		
twig:	The twig files are used to set up templates for the html pages in the application.
		There are 7 twig files:
		- actedIn.twig - shows the actors & the movies they acted in
		- anum.twig - number of actors in each genre in the database
		- directors.twig - first 15 actors in the database
		- home.twig - home page for the web site
		- footer.twig - common footer for each of the html files
		- header.twig - common header for each of the html files
		- form.html.twig - template for forms html files (login and register)
		- register.twig - template for creating a form that allows registration for an account
		- tList.twig - displays all the theaters in the database
		- topTen.twig - displays the top 10 movies in descending order by year
		
		The twig files are found in the public_html/movie/views directory of the source code
		
Silex Files:  Composer was used to compose the needed Service Providers from the Silex 
		Framework.  The code created by composer is found in the vendor directory of the
		source code.  This folder should be stored in a directory called movie that is 
		at the root level of the application.  This code is used by this application and 
		has not been modified.


*****************************************************************************/

// Set time zone  
date_default_timezone_set('America/New_York');

/****************************************************************************   
Silex Setup:
The following code is necessary for one time setup for Silex 
It uses the appropriate services from Silex and Symfony and it
registers the services to the application.
*****************************************************************************/
// Objects we use directly
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Silex\Provider\FormServiceProvider;

// Pull in the Silex code stored in the vendor directory
require_once __DIR__.'/../../silex-files/vendor/autoload.php';

// Create the main application object
$app = new Silex\Application();

// For development, show exceptions in browser
$app['debug'] = true;

// For logging support
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/development.log',
));

// Register validation handler for forms
$app->register(new Silex\Provider\ValidatorServiceProvider());

// Register form handler
$app->register(new FormServiceProvider());

// Register the session service provider for session handling
$app->register(new Silex\Provider\SessionServiceProvider());

// We don't have any translations for our forms, so avoid errors
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
        'translator.messages' => array(),
    ));

// Register the TwigServiceProvider to allow for templating HTML
$app->register(new Silex\Provider\TwigServiceProvider(), array(
        'twig.path' => __DIR__.'/views',
    ));

// Change the default layout 
// Requires including boostrap.css
$app['twig.form.templates'] = array('bootstrap_3_layout.html.twig');

/*************************************************************************
 Database Connection and Queries:
 The following code creates a function that is used throughout the program
 to query the MySQL database.  This section of code also includes the connection
 to the database.  This connection only has to be done once, and the $db object
 is used by the other code.
*****************************************************************************/

// Function for making queries.  The function requires the database connection
// object, the query string with parameters, and the array of parameters to bind
// in the query.  The function uses PDO prepared query statements.
function queryDB($db, $query, $params) {
    // Silex will catch the exception
    $stmt = $db->prepare($query);
    $results = $stmt->execute($params);
    $selectpos = stripos($query, "select");
    if (($selectpos !== false) && ($selectpos < 6)) {
        $results = $stmt->fetchAll();
    }
    return $results;
}


// Connect to the Database at startup, and let Silex catch errors
$app->before(function () use ($app) {
    include '../../connect.php';
    $app['db'] = $db;
});

/*************************************************************************
 Application Code:
 The following code implements the various functionalities of the application, usually
 through different pages.  Each section uses the Silex $app to set up the variables,
 database queries and forms.  Then it renders the pages using twig.

*****************************************************************************/

// Login Page
$app->match('/login', function (Request $request) use ($app) {
	// Use Silex app to create a form with the specified parameters - username and password
	// Form validation is automatically handled using the constraints specified for each
	// parameter
    $form = $app['form.factory']->createBuilder('form')
        ->add('uname', 'text', array(
            'label' => 'User Name',
            'constraints' => array(new Assert\NotBlank())
        ))
        ->add('password', 'password', array(
            'label' => 'Password',
            'constraints' => array(new Assert\NotBlank())
        ))
        ->add('login', 'submit', array('label'=>'Login'))
        ->getForm();
    $form->handleRequest($request);

    // Once the form is validated, get the data from the form and query the database to 
    // verify the username and password are correct
    $msg = '';
    if ($form->isValid()) {
        $db = $app['db'];
        $regform = $form->getData();
        $uname = $regform['uname'];
        $pword = $regform['password'];
        $query = "select password, unum 
        			from Users
        			where u_name = ?";
        $results = queryDB($db, $query, array($uname));
        # Ensure we only get one entry
        if (sizeof($results) == 1) {
            $retrievedPwd = $results[0][0];
            $cnum = $results[0][1];

            // If the username and password are correct, create a login session for the user
            // The session variables are the username and the customer ID to be used in 
            // other queries for lookup.
            if (password_verify($pword, $retrievedPwd)) {
                $app['session']->set('is_user', true);
                $app['session']->set('user', $uname);
                $app['session']->set('unum', $cnum);
                return $app->redirect('/movie/index.php');
            }
        }
        else {
        	$msg = 'Invalid User Name or Password - Try again';
        }
        
    }
    // Use the twig form template to display the login page
    return $app['twig']->render('form.html.twig', array(
        'pageTitle' => 'Login',
        'form' => $form->createView(),
        'results' => $msg
    ));
});


// *************************************************************************

// Registration Page
$app->match('/register', function (Request $request) use ($app) {
    $form = $app['form.factory']->createBuilder('form')
        ->add('uname', 'text', array(
            'label' => 'User Name',
            'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 5)))
        ))
        ->add('password', 'repeated', array(
            'type' => 'password',
            'invalid_message' => 'Password and Verify Password must match',
            'first_options'  => array('label' => 'Password'),
            'second_options' => array('label' => 'Verify Password'),    
            'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 5)))
        ))
        ->add('cname', 'text', array(
            'label' => 'Name',
            'constraints' => array(new Assert\NotBlank())
        ))
        ->add('email', 'text', array(
            'label' => 'Email',
            'constraints' => new Assert\Email()
        ))
        ->add('submit', 'submit', array('label'=>'Register'))
        ->getForm();

    $form->handleRequest($request);

    if ($form->isValid()) {
        $regform = $form->getData();
        $uname = $regform['uname'];
        $pword = $regform['password'];
        $cname = $regform['cname'];
        $email = $regform['email'];
        
        // Check to make sure the username is not already in use
        // If it is, display already in use message
        // If not, hash the password and insert the new customer into the database
        $db = $app['db'];
        $query = 'select * from Users where u_name = ?';
        $results = queryDB($db, $query, array($uname));
        if ($results) {
    		return $app['twig']->render('form.html.twig', array(
        		'pageTitle' => 'Register',
        		'form' => $form->createView(),
        		'results' => 'Username already exists - Try again'
        	));
        }
        else { 
			$hashed_pword = password_hash($pword, PASSWORD_DEFAULT);
			$insertData = array($uname,$hashed_pword,$cname,$email);
       	 	$query = 'insert into Users 
        				(u_name, password, cname, email)
        				values (?, ?, ?, ?)';
        	$results = queryDB($db, $query, $insertData);
	        // Maybe already log the user in, if not validating email
        	return $app->redirect('/movie/index.php');
        }
    }
    return $app['twig']->render('form.html.twig', array(
        'pageTitle' => 'Register',
        'form' => $form->createView(),
        'results' => ''
    ));   
});

// *************************************************************************
 
// Query #1
$app->get('/Movies', function (Silex\Application $app) {
    // Create query 
    $db = $app['db'];
    $Title = $regform['Title'];
    $Year = $regform['Year'];
    $displayData = array($Title,$Year);
    // execute the query 
    $query = "	SELECT MOVIE.Title, MOVIE.Year
		FROM MOVIE
		ORDER BY MOVIE.Year DESC
		LIMIT 15";
    $results = queryDB($db, $query, array($displayData));
    
    // Display results in item page
    return $app['twig']->render('Movies.html.twig', array(
        'pageTitle' => $results[0]['Title'],
        'results' => $results
    ));
});

// *************************************************************************



// *************************************************************************

// Query #2
$app->get('/orders', function (Silex\Application $app) {
    // Create query 
    $db = $app['db'];
    $Names = $regform['Names'];
    $Genre = $regform['Genre'];
    $displayData = array($Names,$Genre);
    // execute the query
    $query = "	SELECT DIRECTOR.Names, DIRECTOR.Genre
		FROM DIRECTOR
		ORDER BY DIRECTOR.Names ASC
		LIMIT 15";
    $results = queryDB($db, $query, array($displayData));
    
    // Display results in item page
    return $app['twig']->render('directors.html.twig', array(
        'pageTitle' => $results[0]['Title'],
        'results' => $results
    ));
});

//*************************************************************************

// Query #3
$app->get('/anum', function (Silex\Application $app) {
    // Create query 
    $db = $app['db'];
    $Genre = $regform['Genre'];
    $num = $regform['Actors'];
    $displayData = array($Genre,$num);
    // execute the query
    $query = "	SELECT ACTOR.Genre, COUNT(ACTOR.Genre) AS 'Actors'
		FROM ACTOR
		GROUP BY ACTOR.Genre";
    $results = queryDB($db, $query, array($displayData));
    
    // Display results in item page
    return $app['twig']->render('anum.html.twig', array(
        'pageTitle' => $results[0]['Title'],
        'results' => $results
    ));
});


//*************************************************************************

// Query #4
$app->get('/tList', function (Silex\Application $app) {
    // Create query
    $db = $app['db'];
    $CompanyName = $regform['CompanyName'];
    $City = $regform['City'];
    $PhoneNumber = $regform['PhoneNumber'];
    $displayData = array($CompanyName,$City,$PhoneNumber);
    // execute the query
    $query = "	SELECT THEATER.CompanyName, THEATER.City, THEATER.PhoneNumber
		FROM THEATER";
    $results = queryDB($db, $query, array($displayData));
    
    // Display results in item page
    return $app['twig']->render('tList.html.twig', array(
        'pageTitle' => $results[0]['Title'],
        'results' => $results
    ));
});


		
// *************************************************************************

// Query #5
$app->get('/actedIn', function (Silex\Application $app) {
    // Create query 
    $db = $app['db'];
    $name = $regform['Names'];
    $Title = $regform['Title'];
    $Year = $regform['Year'];
    $displayData = array($name,$Title,$Year);
    // execute the query
    $query = "SELECT ACTOR.Names, MOVIE.Title, MOVIE.Year
		FROM ACTOR
		INNER JOIN ACTED_IN ON ACTOR.AID = ACTED_IN.AID
		INNER JOIN MOVIE ON MOVIE.ID = ACTED_IN.MID
		ORDER BY MOVIE.Year DESC
		LIMIT 15
";
    $results = queryDB($db, $query, array($displayData));
    
    // Display results in item page
    return $app['twig']->render('actedIn.html.twig', array(
        'pageTitle' => $results[0]['Title'],
        'results' => $results
    ));
});

// *************************************************************************

// Query #6
$app->get('/topTen', function (Silex\Application $app) {
    $db = $app['db'];
    $Title = $regform['Title'];
    $Votes = $regform['Votes'];
    $displayData = array($Title,$Votes);
    // execute the query 
    $query = "	SELECT MOVIE.Title, MOVIE.Votes
		FROM MOVIE
		GROUP BY MOVIE.Title
		ORDER BY MOVIE.Votes DESC
		LIMIT 10";
    $results = queryDB($db, $query, array($displayData));
    
    // Display results in item page
    return $app['twig']->render('topTen.html.twig', array(
        'pageTitle' => $results[0]['Title'],
        'results' => $results
    ));
});
		
// *************************************************************************

// Logout

$app->get('/logout', function () use ($app) {
	$app['session']->clear();
	return $app->redirect('/movie/index.php');
});
	
// *************************************************************************

// Home Page

$app->get('/', function () use ($app) {
	if ($app['session']->get('is_user')) {
		$user = $app['session']->get('user');
	}
	else {
		$user = '';
	}
	return $app['twig']->render('home.twig', array(
        'user' => $user,
        'pageTitle' => 'Home'));
});

// *************************************************************************

// Run the Application

$app->run();