<?php
/**
 * Plugin Name: My Service Worker Plugin
 * Description: A plugin to register a service worker for caching various font types.
 * Version: 1.1
 * Author: Your Name
 */

// Enqueue the service worker registration script
function myswp_enqueue_service_worker_script() {
    wp_enqueue_script('myswp-service-worker', plugin_dir_url(__FILE__) . 'sw-register.js', array(), '1.1', true);
}
add_action('wp_enqueue_scripts', 'myswp_enqueue_service_worker_script');

// Create the service worker registration script
function myswp_create_service_worker_registration_script() {
    $script = "
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('" . plugin_dir_url(__FILE__) . "service-worker.js')
                    .then(function(registration) {
                        console.log('Service Worker Registered', registration);
                    }, function(err) {
                        console.log('Service Worker registration failed: ', err);
                    });
            });
        }
    ";

    file_put_contents(plugin_dir_path(__FILE__) . 'sw-register.js', $script);
}
register_activation_hook(__FILE__, 'myswp_create_service_worker_registration_script');

// Create the service worker script
function myswp_create_service_worker() {
    $content = "
        self.addEventListener('install', function(e) {
            console.log('Service Worker: Installed');
        });

        self.addEventListener('activate', function(e) {
            console.log('Service Worker: Activated');
            e.waitUntil(
                caches.keys().then(function(cacheNames) {
                    return Promise.all(
                        cacheNames.map(function(cache) {
                            if (cache !== 'font-cache') {
                                console.log('Service Worker: Clearing Old Cache');
                                return caches.delete(cache);
                            }
                        })
                    );
                })
            );
        });

        self.addEventListener('fetch', function(e) {
            if (/\\.woff2$|\\.woff$|\\.ttf$|\\.eot$|\\.otf$/.test(e.request.url)) {
                e.respondWith(
                    caches.match(e.request).then(function(response) {
                        return response || fetch(e.request).then(function(response) {
                            let responseClone = response.clone();
                            caches.open('font-cache').then(function(cache) {
                                cache.put(e.request, responseClone);
                            });
                            return response;
                        });
                    })
                );
            }
        });
    ";

    file_put_contents(plugin_dir_path(__FILE__) . 'service-worker.js', $content);
}
register_activation_hook(__FILE__, 'myswp_create_service_worker');
