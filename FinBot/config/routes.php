<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\config\routes.php

/**
 * Configuration des routes de l'application
 * 
 * Ce fichier définit toutes les routes disponibles et les actions correspondantes
 */

return [
    // Routes publiques (accessibles sans connexion)
    'public' => [
        '/' => [
            'controller' => 'HomeController',
            'action' => 'index',
            'file' => 'index.php'
        ],
        '/login' => [
            'controller' => 'AuthController',
            'action' => 'showLogin',
            'file' => 'login.php'
        ],
        '/register' => [
            'controller' => 'AuthController',
            'action' => 'showRegister',
            'file' => 'register.php'
        ],
        '/forgot-password' => [
            'controller' => 'AuthController',
            'action' => 'showForgotPassword',
            'file' => 'forgot-password.php'
        ],
        '/reset-password' => [
            'controller' => 'AuthController',
            'action' => 'showResetPassword',
            'file' => 'reset-password.php'
        ],
        '/about' => [
            'controller' => 'PageController',
            'action' => 'about',
            'file' => 'about.php'
        ],
        '/contact' => [
            'controller' => 'PageController',
            'action' => 'contact',
            'file' => 'contact.php'
        ],
    ],
    
    // Routes authentifiées (nécessitent une connexion)
    'auth' => [
        '/dashboard' => [
            'controller' => 'DashboardController',
            'action' => 'index',
            'file' => 'dashboard.php'
        ],
        '/profile' => [
            'controller' => 'ProfileController',
            'action' => 'index',
            'file' => 'profile.php'
        ],
        '/accounts' => [
            'controller' => 'AccountController',
            'action' => 'index',
            'file' => 'accounts.php'
        ],
        '/accounts/new' => [
            'controller' => 'AccountController',
            'action' => 'new',
            'file' => 'accounts/new.php'
        ],
        '/accounts/view' => [
            'controller' => 'AccountController',
            'action' => 'view',
            'file' => 'accounts/view.php'
        ],
        '/transactions' => [
            'controller' => 'TransactionController',
            'action' => 'index',
            'file' => 'transactions.php'
        ],
        '/transfers' => [
            'controller' => 'TransactionController',
            'action' => 'transfers',
            'file' => 'transfers.php'
        ],
        '/messages' => [
            'controller' => 'MessageController',
            'action' => 'index',
            'file' => 'messages.php'
        ],
        '/messages/view' => [
            'controller' => 'MessageController',
            'action' => 'view',
            'file' => 'messages/view.php'
        ],
        '/messages/new' => [
            'controller' => 'MessageController',
            'action' => 'new',
            'file' => 'messages/new.php'
        ],
        '/loans' => [
            'controller' => 'LoanController',
            'action' => 'index',
            'file' => 'loans.php'
        ],
        '/loans/apply' => [
            'controller' => 'LoanController',
            'action' => 'apply',
            'file' => 'loans/apply.php'
        ],
        '/settings' => [
            'controller' => 'SettingsController',
            'action' => 'index',
            'file' => 'settings.php'
        ],
        '/logout' => [
            'controller' => 'AuthController',
            'action' => 'logout',
            'file' => 'logout.php'
        ],
    ],
    
    // Routes admin (nécessitent rôle administrateur)
    'admin' => [
        '/admin' => [
            'controller' => 'AdminController',
            'action' => 'index',
            'file' => 'admin/index.php'
        ],
        '/admin/users' => [
            'controller' => 'AdminController',
            'action' => 'users',
            'file' => 'admin/users.php'
        ],
        '/admin/accounts' => [
            'controller' => 'AdminController',
            'action' => 'accounts',
            'file' => 'admin/accounts.php'
        ],
        '/admin/transactions' => [
            'controller' => 'AdminController',
            'action' => 'transactions',
            'file' => 'admin/transactions.php'
        ],
        '/admin/loans' => [
            'controller' => 'AdminController',
            'action' => 'loans',
            'file' => 'admin/loans.php'
        ],
        '/admin/account-types' => [
            'controller' => 'AdminController',
            'action' => 'accountTypes',
            'file' => 'admin/account-types.php'
        ],
        '/admin/logs' => [
            'controller' => 'AdminController',
            'action' => 'logs',
            'file' => 'admin/logs.php'
        ],
    ],
    
    // Routes API (pour les requêtes AJAX et l'API)
    'api' => [
        '/api/auth/login' => [
            'controller' => 'ApiAuthController',
            'action' => 'login',
            'file' => 'api/auth/login.php'
        ],
        '/api/auth/register' => [
            'controller' => 'ApiAuthController',
            'action' => 'register',
            'file' => 'api/auth/register.php'
        ],
        '/api/auth/logout' => [
            'controller' => 'ApiAuthController',
            'action' => 'logout',
            'file' => 'api/auth/logout.php'
        ],
        '/api/auth/reset-password' => [
            'controller' => 'ApiAuthController',
            'action' => 'resetPassword',
            'file' => 'api/auth/reset-password.php'
        ],
        '/api/user/profile' => [
            'controller' => 'ApiUserController',
            'action' => 'profile',
            'file' => 'api/user/profile.php'
        ],
        '/api/user/update-profile' => [
            'controller' => 'ApiUserController',
            'action' => 'updateProfile',
            'file' => 'api/user/update-profile.php'
        ],
        '/api/accounts/list' => [
            'controller' => 'ApiAccountController',
            'action' => 'list',
            'file' => 'api/accounts/list.php'
        ],
        '/api/accounts/create' => [
            'controller' => 'ApiAccountController',
            'action' => 'create',
            'file' => 'api/accounts/create.php'
        ],
        '/api/transactions/list' => [
            'controller' => 'ApiTransactionController',
            'action' => 'list',
            'file' => 'api/transactions/list.php'
        ],
        '/api/transactions/create' => [
            'controller' => 'ApiTransactionController',
            'action' => 'create',
            'file' => 'api/transactions/create.php'
        ],
        '/api/transactions/export-csv' => [
            'controller' => 'ApiTransactionController',
            'action' => 'exportCsv',
            'file' => 'api/transactions/export-csv.php'
        ],
        '/api/transactions/export-pdf' => [
            'controller' => 'ApiTransactionController',
            'action' => 'exportPdf',
            'file' => 'api/transactions/export-pdf.php'
        ],
        '/api/dashboard/getData' => [
            'controller' => 'ApiDashboardController',
            'action' => 'getData',
            'file' => 'api/getData.php'
        ],
        '/api/messages/list' => [
            'controller' => 'ApiMessageController',
            'action' => 'list',
            'file' => 'api/messages/list.php'
        ],
        '/api/messages/send' => [
            'controller' => 'ApiMessageController',
            'action' => 'send',
            'file' => 'api/messages/send.php'
        ],
        '/api/loans/apply' => [
            'controller' => 'ApiLoanController',
            'action' => 'apply',
            'file' => 'api/prets/demander.php'
        ],
    ],
    
    // Redirection des routes non trouvées
    '404' => [
        'controller' => 'ErrorController',
        'action' => 'notFound',
        'file' => 'error/404.php'
    ]
];