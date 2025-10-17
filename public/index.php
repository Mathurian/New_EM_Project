<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
use App\Router;

$router = new Router();

$router->get('/', 'HomeController@index');
$router->get('/health', 'HomeController@health');

// users
$router->get('/user/new', 'UserController@new');
$router->get('/users/new', 'UserController@new');
$router->post('/users', 'UserController@create');
$router->get('/admin/users', 'UserController@index');
$router->get('/admin/users/{id}/edit', 'UserController@edit');
$router->post('/admin/users/{id}/update', 'UserController@update');
$router->post('/admin/users/{id}/delete', 'UserController@delete');
$router->post('/admin/users/remove-all-judges', 'UserController@removeAllJudges');
$router->post('/admin/users/remove-all-contestants', 'UserController@removeAllContestants');
$router->post('/admin/users/remove-all-emcees', 'UserController@removeAllEmcees');
$router->post('/admin/users/force-refresh', 'UserController@forceRefresh');

// auth
$router->get('/login', 'AuthController@loginForm');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');

// judge dashboard (assigned subcategories)
$router->get('/judge', 'AuthController@judgeDashboard');
$router->get('/judge/subcategory/{id}', 'AuthController@judgeSubcategoryContestants');

// admin
$router->get('/admin', 'AdminController@index');
$router->get('/admin/api/active-users', 'AdminController@activeUsersApi');
$router->get('/admin/judges', 'AdminController@judges');
$router->post('/admin/judges', 'AdminController@createJudge');
$router->post('/admin/judges/{id}/update', 'AdminController@updateJudge');
$router->post('/admin/judges/delete', 'AdminController@deleteJudge');
$router->get('/admin/contestants', 'AdminController@contestants');
$router->post('/admin/contestants', 'AdminController@createContestant');
$router->post('/admin/contestants/delete', 'AdminController@deleteContestant');
$router->get('/admin/organizers', 'AdminController@organizers');
$router->post('/admin/organizers', 'AdminController@createOrganizer');
$router->post('/admin/organizers/delete', 'AdminController@deleteOrganizer');

// profile
$router->get('/profile', 'ProfileController@edit');
$router->post('/profile', 'ProfileController@update');

// contests
$router->get('/contests', 'ContestController@index');
$router->get('/contests/new', 'ContestController@new');
$router->post('/contests', 'ContestController@create');
$router->post('/contests/{id}/archive', 'ContestController@archive');
$router->get('/admin/archived-contests', 'ContestController@archivedContests');
$router->get('/admin/archived-contest/{id}', 'ContestController@archivedContestDetails');
$router->get('/admin/archived-contest/{id}/print', 'ContestController@archivedContestPrint');
$router->post('/admin/archived-contest/{id}/reactivate', 'ContestController@reactivateContest');
$router->get('/print/contestant/{id}', 'PrintController@contestant');
$router->get('/print/judge/{id}', 'PrintController@judge');
$router->get('/print/category/{id}', 'PrintController@category');
$router->get('/admin/settings', 'AdminController@settings');
$router->post('/admin/settings', 'AdminController@updateSettings');
$router->get('/admin/settings/test-log-level', 'AdminController@testLogLevel');
$router->get('/admin/settings/test-logging', 'AdminController@testLogging');
$router->get('/admin/logs', 'AdminController@logs');
$router->get('/admin/log-files', 'AdminController@logFiles');
$router->get('/admin/log-files/{filename}', 'AdminController@viewLogFile');
$router->get('/admin/log-files/{filename}/download', 'AdminController@downloadLogFile');
$router->post('/admin/log-files/cleanup', 'AdminController@cleanupLogFiles');
$router->get('/admin/backups', 'BackupController@index');
$router->post('/admin/backups/schema', 'BackupController@createSchemaBackup');
$router->post('/admin/backups/full', 'BackupController@createFullBackup');
$router->get('/admin/backups/{id}/download', 'BackupController@downloadBackup');
$router->post('/admin/backups/{id}/delete', 'BackupController@deleteBackup');
$router->post('/admin/backups/settings', 'BackupController@updateSettings');
$router->get('/admin/backups/run-scheduled', 'BackupController@runScheduledBackups');
$router->get('/admin/backups/restore-settings', 'BackupController@restoreBackupSettings');
$router->get('/admin/backups/reset-sessions', 'BackupController@resetSessionVersions');
$router->get('/admin/backups/debug-scheduled', 'BackupController@debugScheduledBackups');
$router->get('/admin/backups/check-time', 'BackupController@checkSystemTime');
$router->get('/admin/backups/debug-settings', 'BackupController@debugBackupSettings');
$router->post('/admin/users/force-logout-all', 'AdminController@forceLogoutAll');
$router->post('/admin/users/{id}/force-logout', 'AdminController@forceLogoutUser');
$router->get('/admin/print-reports', 'AdminController@printReports');
$router->post('/admin/print-reports/email', 'AdminController@emailReport');
$router->get('/admin/emcee-scripts', 'AdminController@emceeScripts');
$router->post('/admin/emcee-scripts', 'AdminController@uploadEmceeScript');
$router->post('/admin/emcee-scripts/{id}/delete', 'AdminController@deleteEmceeScript');
$router->post('/admin/emcee-scripts/{id}/toggle', 'AdminController@toggleEmceeScript');

// database browser
$router->get('/admin/database', 'DatabaseBrowserController@index');
$router->get('/admin/database/table/{table}', 'DatabaseBrowserController@table');
$router->post('/admin/database/query', 'DatabaseBrowserController@query');

$router->get('/contests/{id}/categories', 'CategoryController@index');
$router->get('/contests/{id}/categories/new', 'CategoryController@new');
$router->post('/contests/{id}/categories', 'CategoryController@create');
$router->get('/contests/{id}/subcategories', 'ContestSubcategoryController@index');

// subcategories
$router->get('/categories/{id}/subcategories', 'SubcategoryController@index');
$router->get('/categories/{id}/subcategories/new', 'SubcategoryController@new');
$router->post('/categories/{id}/subcategories', 'SubcategoryController@create');
$router->get('/categories/{id}/subcategories/templates', 'SubcategoryController@templates');
$router->post('/categories/{id}/subcategories/from-template', 'SubcategoryController@createFromTemplate');
$router->post('/categories/{id}/subcategories/bulk-delete', 'SubcategoryController@bulkDelete');
$router->post('/categories/{id}/subcategories/bulk-update', 'SubcategoryController@bulkUpdate');

// emcee
$router->get('/emcee', 'EmceeController@index');
$router->get('/emcee/scripts/{id}/view', 'EmceeController@streamScript');
$router->get('/emcee/contestant/{number}', 'EmceeController@contestantBio');
$router->get('/emcee/judges', 'EmceeController@judgesByCategory');

// templates
$router->get('/admin/templates', 'TemplateController@index');
$router->get('/admin/templates/new', 'TemplateController@new');
$router->post('/admin/templates', 'TemplateController@create');
$router->post('/admin/templates/{id}/delete', 'TemplateController@delete');

// category assignments
$router->get('/categories/{id}/assign', 'CategoryAssignmentController@edit');
$router->post('/categories/{id}/assign', 'CategoryAssignmentController@update');

// people
$router->get('/people', 'PeopleController@index');
$router->post('/contestants', 'PeopleController@createContestant');
$router->post('/judges', 'PeopleController@createJudge');
$router->get('/people/contestants/{id}/edit', 'PeopleController@editContestant');
$router->post('/people/contestants/{id}/update', 'PeopleController@updateContestant');
$router->post('/people/contestants/{id}/delete', 'PeopleController@deleteContestant');
$router->get('/people/contestants/{id}/bio', 'PeopleController@viewContestantBio');
$router->get('/people/judges/{id}/edit', 'PeopleController@editJudge');
$router->post('/people/judges/{id}/update', 'PeopleController@updateJudge');
$router->post('/people/judges/{id}/delete', 'PeopleController@deleteJudge');
$router->get('/people/judges/{id}/bio', 'PeopleController@viewJudgeBio');

// assignments
$router->get('/subcategories/{id}/assign', 'AssignmentController@edit');
$router->post('/subcategories/{id}/assign', 'AssignmentController@update');

// criteria
$router->get('/subcategories/{id}/criteria', 'CriteriaController@index');
$router->get('/subcategories/{id}/criteria/new', 'CriteriaController@new');
$router->post('/subcategories/{id}/criteria', 'CriteriaController@create');
$router->post('/subcategories/{id}/criteria/bulk-delete', 'CriteriaController@bulkDelete');
$router->post('/subcategories/{id}/criteria/bulk-update', 'CriteriaController@bulkUpdate');

// scoring
$router->get('/score/{id}', 'ScoringController@index');
$router->get('/score/{subcategoryId}/contestant/{contestantId}', 'ScoringController@scoreContestant');
$router->post('/score/{id}/submit', 'ScoringController@submit');
$router->post('/score/{id}/unsign', 'ScoringController@unsign');
$router->get('/subcategories/{id}/admin', 'SubcategoryAdminController@edit');
$router->post('/subcategories/{id}/admin', 'SubcategoryAdminController@update');

// results
$router->get('/results', 'ResultsController@resultsIndex');
$router->get('/results/categories', 'ResultsController@categoryIndex');
$router->get('/results/contestants', 'ResultsController@contestantsIndex');
$router->get('/results/contestants/{id}', 'ResultsController@contestantOverview');
$router->get('/results/{id}', 'ResultsController@index');
$router->get('/results/{id}/detailed', 'ResultsController@detailed');
$router->post('/results/contestants/{contestantId}/subcategory/{subcategoryId}/deduction', 'ResultsController@addDeduction');
$router->post('/results/{subcategoryId}/contestant/{contestantId}/deduction', 'ResultsController@addDeduction');
$router->get('/results/contestant/{contestantId}/category/{categoryId}', 'ResultsController@contestantDetailed');
$router->post('/results/{id}/unsign-all', 'ResultsController@unsignAll');
$router->post('/results/category/{categoryId}/unsign-all', 'ResultsController@unsignAllByCategory');
$router->post('/results/contestant/{contestantId}/unsign-all', 'ResultsController@unsignAllByContestant');
$router->post('/results/judge/{judgeId}/unsign-all', 'ResultsController@unsignAllByJudge');
$router->get('/admin/contestant/{contestantId}/scores', 'AdminController@contestantScores');

$router->dispatch();


