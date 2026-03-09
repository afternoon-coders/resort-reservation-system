#!/usr/bin/env php
<?php
/**
 * Cron Job: Auto-update reservation statuses
 * 
 * Run this every 5-15 minutes via crontab instead of on every page load.
 * Example crontab entry:
 *   Every 10 min: /usr/bin/php /var/www/resort-reservation-system/scripts/cron_update_statuses.php >> /var/www/resort-reservation-system/logs/cron.log 2>&1
 */

require_once __DIR__ . '/../helpers/ReservationModel.php';

try {
    $reservationModel = new ReservationModel();
    $reservationModel->autoUpdateStatuses();
    echo date('Y-m-d H:i:s') . " - Reservation statuses updated successfully.\n";
} catch (Exception $e) {
    error_log('Cron autoUpdateStatuses error: ' . $e->getMessage());
    echo date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n";
    exit(1);
}
