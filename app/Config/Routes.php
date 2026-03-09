<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'HomeController::index');
$routes->get('search', 'HomeController::index');

$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::loginPost');
$routes->get('register', 'AuthController::register');
$routes->post('register', 'AuthController::registerPost');
$routes->get('logout', 'AuthController::logout');
$routes->post('google-auth', 'AuthController::googleAuth');
$routes->get('verify', 'AuthController::verify');

$routes->get('product/(:num)', 'ProductController::details/$1');
$routes->get('sell', 'ProductController::sell', ['filter' => 'auth']);
$routes->post('sell', 'ProductController::sellPost', ['filter' => 'auth']);

$routes->group('profile', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'ProfileController::index');
    $routes->get('settings', 'ProfileController::settings');
    $routes->post('settings', 'ProfileController::updateSettings');
    $routes->get('orders', 'ProfileController::orders');
    $routes->get('order/(:num)', 'ProfileController::orderDetail/$1');
});

$routes->get('wishlist', 'PageController::wishlist', ['filter' => 'auth']);
$routes->get('blogs', 'PageController::blogs');
$routes->get('blog/(:num)', 'PageController::blogDetail/$1');
$routes->get('stores', 'PageController::stores');
$routes->get('about', 'PageController::about');
$routes->get('terms', 'PageController::terms');
$routes->get('privacy', 'PageController::privacy');
$routes->get('founders', 'PageController::founders');
$routes->get('refund', 'PageController::refund');
$routes->get('requests', 'PageController::requests');
$routes->post('requests', 'PageController::requestPost', ['filter' => 'auth']);
$routes->get('page/(:segment)', 'PageController::customPage/$1');

$routes->get('shipping', 'OrderController::shipping', ['filter' => 'auth']);
$routes->post('shipping', 'OrderController::shippingPost', ['filter' => 'auth']);
$routes->get('payment', 'OrderController::payment', ['filter' => 'auth']);
$routes->post('payment', 'OrderController::paymentPost', ['filter' => 'auth']);
$routes->get('payment-method', 'OrderController::paymentMethod', ['filter' => 'auth']);
$routes->post('place-order', 'OrderController::placeOrder', ['filter' => 'auth']);
$routes->get('order-summary/(:num)', 'OrderController::summary/$1', ['filter' => 'auth']);

$routes->group('api', static function ($routes) {
    $routes->post('chat/send', 'ApiController::chatSend');
    $routes->get('chat/messages/(:num)', 'ApiController::chatMessages/$1');
    $routes->get('search', 'ApiController::search');
    $routes->post('wishlist/toggle', 'ApiController::wishlistToggle');
    $routes->post('bulk-upload', 'ApiController::bulkUpload');
    $routes->post('validate-coupon', 'ApiController::validateCoupon');
    $routes->post('update-listing', 'ApiController::updateListing');
    $routes->post('bulk-listing-action', 'ApiController::bulkListingAction');
});

$routes->group('admin', ['filter' => 'admin'], static function ($routes) {
    $routes->get('/', 'Admin\DashboardController::index');
    $routes->get('dashboard', 'Admin\DashboardController::index');
    $routes->get('analytics', 'Admin\DashboardController::analytics');

    $routes->get('users', 'Admin\UsersController::index');
    $routes->post('users/update', 'Admin\UsersController::update');
    $routes->post('users/delete', 'Admin\UsersController::delete');

    $routes->get('listings', 'Admin\ListingsController::index');
    $routes->post('listings/approve', 'Admin\ListingsController::approve');
    $routes->post('listings/reject', 'Admin\ListingsController::reject');
    $routes->post('listings/delete', 'Admin\ListingsController::delete');

    $routes->get('transactions', 'Admin\TransactionsController::index');
    $routes->post('transactions/update', 'Admin\TransactionsController::update');

    $routes->get('returns', 'Admin\ReturnsController::index');
    $routes->post('returns/update', 'Admin\ReturnsController::update');

    $routes->get('support', 'Admin\SupportController::index');
    $routes->post('support/reply', 'Admin\SupportController::reply');
    $routes->post('support/update', 'Admin\SupportController::update');

    $routes->get('chats', 'Admin\ChatsController::index');

    $routes->get('blogs', 'Admin\BlogsController::index');
    $routes->post('blogs/create', 'Admin\BlogsController::create');
    $routes->post('blogs/update', 'Admin\BlogsController::update');
    $routes->post('blogs/delete', 'Admin\BlogsController::delete');

    $routes->get('pages', 'Admin\PagesController::index');
    $routes->post('pages/save', 'Admin\PagesController::save');
    $routes->post('pages/create', 'Admin\PagesController::create');
    $routes->post('pages/update', 'Admin\PagesController::update');
    $routes->post('pages/delete', 'Admin\PagesController::delete');

    $routes->get('banners', 'Admin\BannersController::index');
    $routes->post('banners/upload', 'Admin\BannersController::upload');
    $routes->post('banners/update', 'Admin\BannersController::update');
    $routes->post('banners/delete', 'Admin\BannersController::delete');

    $routes->get('requests', 'Admin\RequestsController::index');
    $routes->post('requests/update', 'Admin\RequestsController::update');

    $routes->get('roles', 'Admin\RolesController::index');
    $routes->post('roles/create', 'Admin\RolesController::create');
    $routes->post('roles/update', 'Admin\RolesController::update');
    $routes->post('roles/delete', 'Admin\RolesController::delete');
    $routes->post('roles/assign', 'Admin\RolesController::assign');

    $routes->get('activity', 'Admin\ActivityController::index');

    $routes->get('security', 'Admin\SecurityController::index');
    $routes->post('security/blacklist', 'Admin\SecurityController::blacklist');
    $routes->post('security/remove-blacklist', 'Admin\SecurityController::removeBlacklist');
    $routes->post('security/terminate-session', 'Admin\SecurityController::terminateSession');

    $routes->get('settings', 'Admin\SettingsController::index');
    $routes->post('settings/update', 'Admin\SettingsController::update');

    $routes->get('email-templates', 'Admin\EmailTemplatesController::index');
    $routes->post('email-templates/update', 'Admin\EmailTemplatesController::update');
    $routes->post('email-templates/create', 'Admin\EmailTemplatesController::create');
    $routes->post('email-templates/toggle', 'Admin\EmailTemplatesController::toggle');
    $routes->post('email-templates/test', 'Admin\EmailTemplatesController::testSend');
});
