<?php
namespace Destiny\Common\GeoIP2;

/**
 * @method static GeoIP2Service instance()
 */
class GeoIP2Service extends Service {

    /**
     * @param string $ip
     * @return int
     */
    public function getASNByIP($ip) {
        $db = Application::getGeoIP2DB();
        $record = $db->isp($ip);
        return $record->autonomousSystemNumber;
    }

}
