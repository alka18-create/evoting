<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', App\Http\Controllers\HealthController::class)->name('health');

// Auth Routes with Rate Limiting
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.submit');

    // Password Reset
    Route::get('/forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // P3-01: tantangan MFA (di luar middleware mfa agar tidak redirect loop).
    Route::get('/mfa/challenge', [App\Http\Controllers\Auth\MfaChallengeController::class, 'show'])->name('mfa.challenge');
    Route::post('/mfa/challenge', [App\Http\Controllers\Auth\MfaChallengeController::class, 'verify'])
        ->middleware('throttle:login')
        ->name('mfa.challenge.verify');
});

// Admin Routes (P3-01: wajib lolos MFA per sesi)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'mfa', 'role:SUPER_ADMIN,ADMIN,OPERATOR'])->group(function () {
    Route::get('/', App\Http\Controllers\Admin\DashboardController::class)->name('dashboard');

    // Organization Management
    Route::get('organizations/{organization}/delete-confirm', [App\Http\Controllers\Admin\OrganizationController::class, 'deleteConfirm'])->name('organizations.delete-confirm');
    Route::resource('organizations', App\Http\Controllers\Admin\OrganizationController::class)->except(['show']);

    // Voting Event Management (paket serentak)
    Route::get('voting-events/{votingEvent}/delete-confirm', [App\Http\Controllers\Admin\VotingEventController::class, 'deleteConfirm'])->name('voting-events.delete-confirm');
    Route::resource('voting-events', App\Http\Controllers\Admin\VotingEventController::class);
    Route::post('voting-events/{votingEvent}/elections/bulk', [App\Http\Controllers\Admin\VotingEventController::class, 'bulkCreateElections'])->name('voting-events.elections.bulk');
    Route::post('voting-events/{votingEvent}/schedule', [App\Http\Controllers\Admin\VotingEventController::class, 'schedule'])->name('voting-events.schedule');
    Route::post('voting-events/{votingEvent}/open', [App\Http\Controllers\Admin\VotingEventController::class, 'open'])->name('voting-events.open');
    Route::post('voting-events/{votingEvent}/close', [App\Http\Controllers\Admin\VotingEventController::class, 'close'])->name('voting-events.close');
    Route::post('voting-events/{votingEvent}/archive', [App\Http\Controllers\Admin\VotingEventController::class, 'archive'])->name('voting-events.archive');

    // Voting Event Tokens (1 token untuk semua)
    Route::get('voting-events/{votingEvent}/tokens', [App\Http\Controllers\Admin\VotingEventTokenController::class, 'index'])->name('voting-events.tokens.index');
    Route::post('voting-events/{votingEvent}/tokens/issue', [App\Http\Controllers\Admin\VotingEventTokenController::class, 'issue'])->name('voting-events.tokens.issue');
    Route::post('voting-events/{votingEvent}/tokens/issue-all', [App\Http\Controllers\Admin\VotingEventTokenController::class, 'issueAll'])->name('voting-events.tokens.issue-all');
    Route::post('voting-events/{votingEvent}/tokens/assign-all', [App\Http\Controllers\Admin\VotingEventTokenController::class, 'assignAll'])->name('voting-events.tokens.assign-all');
    Route::get('voting-events/{votingEvent}/tokens/print-bulk', [App\Http\Controllers\Admin\VotingEventTokenController::class, 'printCardsBulk'])->name('voting-events.tokens.print-bulk');
    Route::get('voting-events/{votingEvent}/tokens/cards-pdf/{file}', [App\Http\Controllers\Admin\VotingEventTokenController::class, 'downloadCardPdf'])->where('file', '[A-Za-z0-9\-\.]+')->name('voting-events.tokens.card-pdf.download');
    Route::delete('voting-events/{votingEvent}/tokens/cards-pdf/{file}', [App\Http\Controllers\Admin\VotingEventTokenController::class, 'destroyCardPdf'])->where('file', '[A-Za-z0-9\-\.]+')->name('voting-events.tokens.card-pdf.destroy');
    Route::get('voting-events/{votingEvent}/tokens/{votingEventVoter}/print', [App\Http\Controllers\Admin\VotingEventTokenController::class, 'printCard'])->name('voting-events.tokens.print-card');
    Route::post('voting-events/{votingEvent}/tokens/{votingEventVoter}/reissue', [App\Http\Controllers\Admin\VotingEventTokenController::class, 'reissue'])->name('voting-events.tokens.reissue');
    Route::delete('voting-events/{votingEvent}/tokens/{votingEventVoter}', [App\Http\Controllers\Admin\VotingEventTokenController::class, 'destroy'])->name('voting-events.tokens.destroy');

    // Election Management
    Route::get('elections/{election}/delete-confirm', [App\Http\Controllers\Admin\ElectionController::class, 'deleteConfirm'])->name('elections.delete-confirm');
    Route::resource('elections', App\Http\Controllers\Admin\ElectionController::class);
    Route::post('elections/{election}/schedule', [App\Http\Controllers\Admin\ElectionController::class, 'schedule'])->name('elections.schedule');
    Route::post('elections/{election}/open', [App\Http\Controllers\Admin\ElectionController::class, 'open'])->name('elections.open');
    Route::post('elections/{election}/close', [App\Http\Controllers\Admin\ElectionController::class, 'close'])->name('elections.close');
    Route::post('elections/{election}/archive', [App\Http\Controllers\Admin\ElectionController::class, 'archive'])->name('elections.archive');

    // Candidate Management
    Route::resource('elections.candidates', App\Http\Controllers\Admin\CandidateController::class);

    // User Management (SUPER_ADMIN only)
    Route::get('users', [App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [App\Http\Controllers\Admin\UserController::class, 'create'])->name('users.create');
    Route::post('users', [App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}/edit', [App\Http\Controllers\Admin\UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');
    Route::post('users/{user}/toggle-active', [App\Http\Controllers\Admin\UserController::class, 'toggleActive'])->name('users.toggle-active');

    // Voter Management
    Route::get('voters', [App\Http\Controllers\Admin\VoterController::class, 'index'])->name('voters.index');
    Route::get('voters/create', [App\Http\Controllers\Admin\VoterController::class, 'create'])->name('voters.create');
    Route::post('voters', [App\Http\Controllers\Admin\VoterController::class, 'store'])->name('voters.store');
    Route::get('voters/export', [App\Http\Controllers\Admin\VoterController::class, 'export'])->name('voters.export');
    Route::get('voters/template', [App\Http\Controllers\Admin\VoterController::class, 'downloadTemplate'])->name('voters.template');
    Route::post('voters/import', [App\Http\Controllers\Admin\VoterController::class, 'import'])->name('voters.import');
    Route::get('voters/{voter}/delete-confirm', [App\Http\Controllers\Admin\VoterController::class, 'deleteConfirm'])->name('voters.delete-confirm')->whereNumber('voter');
    Route::get('voters/{voter}', [App\Http\Controllers\Admin\VoterController::class, 'show'])->name('voters.show')->whereNumber('voter');
    Route::get('voters/{voter}/edit', [App\Http\Controllers\Admin\VoterController::class, 'edit'])->name('voters.edit')->whereNumber('voter');
    Route::put('voters/{voter}', [App\Http\Controllers\Admin\VoterController::class, 'update'])->name('voters.update')->whereNumber('voter');
    Route::delete('voters/{voter}', [App\Http\Controllers\Admin\VoterController::class, 'destroy'])->name('voters.destroy')->whereNumber('voter');
    Route::post('voters/{voter}/toggle-active', [App\Http\Controllers\Admin\VoterController::class, 'toggleActive'])->name('voters.toggle-active')->whereNumber('voter');

    // Token Management (standalone overview)
    Route::get('tokens', [App\Http\Controllers\Admin\TokenController::class, 'overview'])->name('tokens.overview');

    // Token Management (per election)
    Route::get('elections/{election}/tokens', [App\Http\Controllers\Admin\TokenController::class, 'index'])->name('elections.tokens.index');
    Route::post('elections/{election}/tokens/issue', [App\Http\Controllers\Admin\TokenController::class, 'issue'])->name('elections.tokens.issue');
    Route::get('elections/{election}/tokens/print-bulk', [App\Http\Controllers\Admin\TokenController::class, 'printCardsBulk'])->name('elections.tokens.print-bulk');
    Route::get('elections/{election}/tokens/cards-pdf/{file}', [App\Http\Controllers\Admin\TokenController::class, 'downloadCardPdf'])->where('file', '[A-Za-z0-9\-\.]+')->name('elections.tokens.card-pdf.download');
    Route::delete('elections/{election}/tokens/cards-pdf/{file}', [App\Http\Controllers\Admin\TokenController::class, 'destroyCardPdf'])->where('file', '[A-Za-z0-9\-\.]+')->name('elections.tokens.card-pdf.destroy');
    Route::get('elections/{election}/tokens/{eligibility}/print', [App\Http\Controllers\Admin\TokenController::class, 'printCard'])->name('elections.tokens.print-card');
    Route::post('elections/{election}/tokens/{eligibility}/reissue', [App\Http\Controllers\Admin\TokenController::class, 'reissue'])->name('elections.tokens.reissue');
    Route::delete('elections/{election}/tokens/{eligibility}', [App\Http\Controllers\Admin\TokenController::class, 'destroy'])->name('elections.tokens.destroy');

    // Results
    Route::get('results', [App\Http\Controllers\Admin\ResultController::class, 'index'])->name('results.index');
    Route::get('results/{election}', [App\Http\Controllers\Admin\ResultController::class, 'show'])->name('results.show');
    Route::get('results/{election}/export', [App\Http\Controllers\Admin\ResultController::class, 'export'])->name('results.export');
    Route::get('results/{election}/export-excel', [App\Http\Controllers\Admin\ResultController::class, 'exportExcel'])->name('results.export-excel');
    Route::get('results/{election}/export-pdf', [App\Http\Controllers\Admin\ResultController::class, 'exportPdf'])->name('results.export-pdf');

    // Audit Logs
    Route::get('audit-logs', [App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('audit-logs/export', [App\Http\Controllers\Admin\AuditLogController::class, 'export'])->name('audit-logs.export');
    Route::get('audit-logs/{auditLog}', [App\Http\Controllers\Admin\AuditLogController::class, 'show'])->name('audit-logs.show');

    // Backup Management
    Route::get('backups', [App\Http\Controllers\Admin\BackupController::class, 'index'])->name('backups.index');
    Route::post('backups', [App\Http\Controllers\Admin\BackupController::class, 'create'])->name('backups.create');
    Route::get('backups/{filename}/download', [App\Http\Controllers\Admin\BackupController::class, 'download'])->name('backups.download');
    Route::delete('backups/{filename}', [App\Http\Controllers\Admin\BackupController::class, 'destroy'])->name('backups.destroy');

    // QR Scanner (P1-03: batasi brute force verify)
    Route::get('scan', [App\Http\Controllers\Admin\ScanController::class, 'show'])->name('scan.show');
    Route::post('scan/verify', [App\Http\Controllers\Admin\ScanController::class, 'verify'])
        ->middleware('throttle:scan-verify')
        ->name('tokens.scan-verify');

    // Profile
    Route::get('profile', [App\Http\Controllers\Admin\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [App\Http\Controllers\Admin\ProfileController::class, 'changePassword'])->name('profile.password');

    // P3-01: kelola MFA TOTP
    Route::get('profile/mfa', [App\Http\Controllers\Admin\MfaController::class, 'show'])->name('profile.mfa');
    Route::post('profile/mfa/enable', [App\Http\Controllers\Admin\MfaController::class, 'enable'])->name('profile.mfa.enable');
    Route::post('profile/mfa/disable', [App\Http\Controllers\Admin\MfaController::class, 'disable'])->name('profile.mfa.disable');
});

Route::redirect('/voter/login', '/vote/login');

// Voter Routes
Route::prefix('vote')->name('vote.')->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\VoterLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\VoterLoginController::class, 'login'])
        ->middleware('throttle:vote-login')
        ->name('login.submit');

    Route::post('/logout', [App\Http\Controllers\Auth\VoterLoginController::class, 'logout'])
        ->name('logout');

    Route::middleware(['auth:voter', 'voter.timeout', 'role:VOTER'])->group(function () {
        // Wizard batch (1 token untuk semua organisasi)
        Route::get('/wizard/step/{step}', [App\Http\Controllers\Voter\WizardController::class, 'step'])->name('wizard.step');
        Route::post('/wizard/step/{step}', [App\Http\Controllers\Voter\WizardController::class, 'storeStep'])->name('wizard.storeStep');
        Route::get('/wizard/review', [App\Http\Controllers\Voter\WizardController::class, 'review'])->name('wizard.review');
        Route::post('/wizard/submit', [App\Http\Controllers\Voter\WizardController::class, 'submit'])
            ->middleware('throttle:vote-submit')
            ->name('wizard.submit');

        // Legacy single-election fallback
        Route::get('/', [App\Http\Controllers\Voter\VotingController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\Voter\VotingController::class, 'vote'])
            ->middleware('throttle:vote-submit')
            ->name('submit');
    });

    // Confirmation page (no auth required - voter already logged out after voting)
    Route::get('/confirmation', [App\Http\Controllers\Voter\VotingController::class, 'confirmation'])->name('confirmation');
});

