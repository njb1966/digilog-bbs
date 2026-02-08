<?php
/**
 * Door Drop File Generator
 * Generates DOOR.SYS, DORINFO1.DEF, and other door info files
 */

class DoorDropGenerator {
    
    /**
     * Generate DOOR.SYS file (most common format)
     */
    public static function generateDoorSys($user, $node = 1, $filepath = null) {
        $lines = [
            'COM1:',
            '57600',
            '8',
            $node,
            '57600',
            'Y',
            'Y',
            'Y',
            'Y',
            self::padOrTruncate($user['username'], 30),
            self::padOrTruncate('Local', 25),
            '555-555-5555',
            '555-555-5555',
            '',
            '110',
            '1440',
            '-1.0',
            date('m/d/y'),
            $node,
            'Y',
            'N',
            'Y',
            '25',
            'N',
            '1,2,3,4,5,6,7',
            '1',
            '01/01/90',
            '32768',
            '32768',
            '1440',
            '999999',
            date('m/d/y'),
            '/tmp/',
            '/tmp/',
            'Digilog BBS',
            self::padOrTruncate($user['username'], 30),
            '00:05',
            'Y',
            'Y',
            'Y',
            '7',
            '1440',
            '1440',
        ];
        
        $content = implode("\r\n", $lines);
        
        if ($filepath) {
            file_put_contents($filepath, $content);
        }
        
        return $content;
    }
    
    /**
     * Generate DORINFO1.DEF file
     */
    public static function generateDorinfodef($user, $node = 1, $filepath = null) {
        $lines = [
            'Digilog BBS',
            'Sysop',
            'Admin',
            'COM1',
            '57600 BAUD,N,8,1',
            '0',
            self::padOrTruncate($user['username'], 30),
            self::padOrTruncate($user['username'], 30),
            '110',
            '1440',
            '1',
        ];
        
        $content = implode("\r\n", $lines);
        
        if ($filepath) {
            file_put_contents($filepath, $content);
        }
        
        return $content;
    }
    
    /**
     * Pad or truncate string to exact length
     */
    private static function padOrTruncate($str, $length) {
        $str = substr($str, 0, $length);
        return str_pad($str, $length);
    }
}
