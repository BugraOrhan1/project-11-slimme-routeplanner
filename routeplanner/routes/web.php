<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ChangeLogController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InspectorController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MyRouteController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    // Inspecteur: eigen route (US19, US21, US22)
    Route::get('/mijn-route', [MyRouteController::class, 'show'])->name('my-route');
    Route::post('/mijn-route/stops/{stop}/start', [MyRouteController::class, 'start'])->name('my-route.start');
    Route::post('/mijn-route/stops/{stop}/finish', [MyRouteController::class, 'finish'])->name('my-route.finish');

    // Meldingen
    Route::get('/meldingen', [AlertController::class, 'index'])->name('alerts.index');
    Route::post('/meldingen/{alert}/gelezen', [AlertController::class, 'read'])->name('alerts.read');
    Route::post('/meldingen/alles-gelezen', [AlertController::class, 'readAll'])->name('alerts.read-all');

    // Kantoor: planner, bedrijfsleider, technisch manager, administratie, beheerder
    Route::middleware('role:office')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/opdrachten/importeren', [AssignmentController::class, 'importForm'])->name('assignments.import');
        Route::post('/opdrachten/importeren', [AssignmentController::class, 'import'])->name('assignments.import.store');
        Route::resource('opdrachten', AssignmentController::class)
            ->parameters(['opdrachten' => 'assignment'])->names('assignments')->except('show');

        Route::resource('klanten', CustomerController::class)
            ->parameters(['klanten' => 'customer'])->names('customers')->except(['create', 'edit']);
        Route::resource('klanten.locaties', LocationController::class)
            ->parameters(['klanten' => 'customer', 'locaties' => 'location'])->names('locations')
            ->except(['index', 'show'])->shallow();

        Route::get('/werkzaamheden', [ActivityController::class, 'index'])->name('activities.index');
        Route::post('/activiteiten', [ActivityController::class, 'store'])->name('activities.store');
        Route::put('/activiteiten/{activity}', [ActivityController::class, 'update'])->name('activities.update');
        Route::delete('/activiteiten/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');
        Route::post('/certificaten', [CertificateController::class, 'store'])->name('certificates.store');
        Route::put('/certificaten/{certificate}', [CertificateController::class, 'update'])->name('certificates.update');
        Route::delete('/certificaten/{certificate}', [CertificateController::class, 'destroy'])->name('certificates.destroy');

        Route::resource('inspecteurs', InspectorController::class)
            ->parameters(['inspecteurs' => 'inspector'])->names('inspectors')->except('show');

        Route::get('/planning', [PlanningController::class, 'index'])->name('planning.index');
        Route::get('/planning/nieuw', [PlanningController::class, 'create'])->name('planning.create');
        Route::post('/planning/voorstel', [PlanningController::class, 'propose'])->name('planning.propose');
        Route::post('/planning/alles-goedkeuren', [PlanningController::class, 'approveAll'])->name('planning.approve-all');

        Route::get('/routes/{route}', [RouteController::class, 'show'])->name('routes.show');
        Route::put('/routes/{route}/volgorde', [RouteController::class, 'reorder'])->name('routes.reorder');
        Route::post('/routes/{route}/stops', [RouteController::class, 'addStop'])->name('routes.stops.add');
        Route::delete('/routes/{route}/stops/{stop}', [RouteController::class, 'removeStop'])->name('routes.stops.remove');
        Route::post('/routes/{route}/stops/{stop}/verplaatsen', [RouteController::class, 'moveStop'])->name('routes.stops.move');
        Route::post('/routes/{route}/goedkeuren', [RouteController::class, 'approve'])->name('routes.approve');
        Route::post('/routes/{route}/terug-naar-voorstel', [RouteController::class, 'unapprove'])->name('routes.unapprove');
        Route::delete('/routes/{route}', [RouteController::class, 'destroy'])->name('routes.destroy');

        Route::get('/wijzigingen', [ChangeLogController::class, 'index'])->name('changes.index');

        Route::middleware('role:beheerder,bedrijfsleider')->group(function () {
            Route::resource('gebruikers', UserController::class)
                ->parameters(['gebruikers' => 'user'])->names('users')->only(['index', 'store', 'update', 'destroy']);
        });
    });
});
