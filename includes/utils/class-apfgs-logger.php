<?php
// This file is intentionally blank.
// It's a placeholder for logger utility. 

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'APFGS_Logger' ) ) {

    class APFGS_Logger {

        public static function log( $message ) {
            if ( WP_DEBUG === true && WP_DEBUG_LOG === true ) {
                if ( is_array( $message ) || is_object( $message ) ) {
                    error_log( print_r( $message, true ) );
                } else {
                    error_log( $message );
                }
            }
            // Optionally, you could extend this to write to a dedicated plugin log file
            // or integrate with a more advanced logging library if needed.
        }

        public static function debug( $message ) {
            // You might want a separate method for debug messages that only logs
            // if a specific plugin debug constant is also true.
            // For now, it will behave the same as log().
            self::log( "[DEBUG] " . $message );
        }

        public static function error( $message ) {
            self::log( "[ERROR] " . $message );
            // Potentially add more error-specific handling, like emailing an admin.
        }
    }

} 