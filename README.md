In this manual, we want to guide you in all installation steps of the application, as well as the use by users, and management sections by administrator.


## Installation manual ##

In our system, we have to be installed php5, mysql y apache2. For Symfony installation, we need "composer" application.
Versions used were:

	mySQL 5.5.24
	php 5.4.3
	apache 2.4.2

We can get installation instructions of composer here:

[Composer](http://getcomposer.org/)

Also, php5-xsl and php5-intl extensions are needed to stay loaded:

	[cli]
	sudo apt-get install php5-xsl
	sudo apt-get install php5-intl

First, we need to install our project on server folder. We recommend Git use to perform this action, or copying PFC/repo folder in the new folder.

	[cli]
	git clone [unidad]/PFC/repo/.git directorioDestino

	or

	git clone ssh://git@github.com:arjona00/learning-objects-evaluation-platform.git directorioDestino

	or

	cp -R [unidad]/PFC/repo directorioDestino


Once we are in destination folder, install application. Execute:

	[cli]
	~/composer.phar install

Composer application is usually installed on user's logged home folder.

When vendor download is finished, as shows composer.json file, installation is going to require initial configuration, that will be saved on app/config/parameters.yml file. This configuration includes engine, name, user and password of database.
Now we show usually configuration file:

	[yaml]
    database_driver: pdo_mysql
    database_host: 127.0.0.1
    database_port: null
    database_name: db_pfc
    database_user: root
    database_password: xxxxx
    mailer_transport: smtp
    mailer_host: 127.0.0.1
    mailer_user: null
    mailer_password: null
    locale: en
    secret: ThisTokenIsNotSoSecretChangeIt

If database hasn't been created previously with phpmyadmin or console commands, now we can do it with doctrine commands.

	[cli]
	php app/console doctrine:database:create
	php app/console doctrine:schema:create

Execute data fixtures, that fill database with initial test data. If we want to introduce final subjets data, we can edit src/PFC/ModelBundle/DataFixtures/ORM/LoadsubjetsData.php file.

- arraysubjets: subjets data.

- arraysubjetCategories: categories data.

- arrayCategorySubcategories: subcategories data.

- arrayQuestions: Questions and answers.


	[cli]
	php app/console doctrine:fixture:load

Now that database has been created and filled, we configure restricted access to administration section. Edit app/config/security.yml file.
Here you can find created roles, ROLE_ADMIN y ROLE_SUPER_ADMIN.

	[yaml]
	admin:  { password: XXXX, roles: [ 'ROLE_SUPER_ADMIN' ] }
	profesor1: { password: XXXX, roles: [ 'ROLE_ADMIN' ] }

We need to modify this file and create as many teachers as we wish and/or set passwords.

Symfony use two folders to save cache and logs files, and we have to set the next permissions:

	[cli]
	rm -rf app/logs/*
	rm -rf app/cache/*
	chmod 777 app/logs
	chmod 777 app/cache

Finally, execute asset resources, clear cache and compress resource files. This sequence should be perform when you modify resources as css, js or images.

	[cli]
	php app/console assets:install
	php app/console cache:clear
	php app/console cache:clear --env=prod
	php app/console assetic:dump

Only remains to configure apache's sites and /etc/hosts file in order to point this to our web, located in web folder of application.


## User's manual ##

This website application allow users to perform questionnaires of registered subjets and its themes. Each theme can include some subcategories of which you can do questionnaires.

To do that, you access to website application employing yours web navigator. Choose subjet you wish to do questionnaires, from subjets menu or home page.

Once you are in subjet page, choose doing a complete subjet questionnaire, theme questionnaire or subcategory questionnaire. Click on your election.

For each questionnaire, it can exist many difficulty levels. There are levels of 1 to 10, but also you can do a questionnaire of random questions of any level.

Now, a maximum of 10 questions appear on you screen. Respond questions and click on "Send" button. A new page will be loaded with the questionnaire result.


## Administrator's manual ##

subjets' administrators, have the responsibility of create subjets, create themes of them, and for each theme, as many subcategories as are necessary.
Also, he preforms questions' import and export tasks.

For doing that, administrator can access to website from web navigator.

In the menu, click on "Administración".

The system shows login access form, and request login and password.

If access is granted, a list of subjets, with its categories and subcategories, will be shown.


### Creating elements ###

To create a new subjet, you only need to shift to the end of page, and click on "Nueva asignatura". In edition dialog box, introduce the subjet's name, and click on confirm button.

The subjet has been created. Click on reload button in the right side of the new subjet, and you can see the subjet in subjet list with alphabetical order.
Now you can edit its fields.

For categories and subcategories, you can perform a similar method. Go to the end of subjet' theme list. Click on "Nuevo tema". Introduce new theme's name and confirm.
After that, click on reload button, on the right side of the new theme. You should see the new created theme, and the editable fields, title and description.


### Editing elements ###

You can edit all elements clicking on its field. A edit dialog box appears, make the change and click on confirm button. Data will be updated immediately.


### Deleting elements ###

In order to delete any element, click on delete button (a white X over red background). Remember that eliminating any element, it also eliminate all structures that have relation with erased element. (i.e: Deleting a subjet, will erase all its categories, subcategories, questions and answers).

Confirm deletion in the dialog box.


### Importing and exporting questions ###

#### Exporting ####

Exportation can be performed over any element that can contain questions, like subjets, categories and subcategories.

From subjet administration list, click on edition button (white arrow over blue background). This will go to importation and exportation page.

To perform an exportation, choose the exportation format type and click on "Exportar".

Optionally you can choose a specific level, and the maximum number of question to export.


#### Importing ####

Importing must be done in any subcategory. From subjet administration list, click on edition button (white arrow over blue background). This will go to importation and exportation page.

Click on "Importar".

In the new showed page, select the importing format type. Click on "Seleccionar fichero" to select the file to import.

A dialog box will be shown to select files.

Once the file has been uploaded, click on "Procesar fichero". If file accomplishes type and format conditions, the file will be processed, and questions will be imported in the chosen subcategory.
