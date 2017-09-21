<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Positioning library interface
 *
 * Startup loads main class, which is used for all operations.
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_interface extends midcom_baseclasses_components_interface
{
    // TODO: cron entries
    public function _on_watched_dba_create($object)
    {
        if ($object->context == 'geo') {
            $this->_geotag($object);
        }
    }

    public function _on_watched_dba_update($object)
    {
        if ($object->context == 'geo') {
            $this->_geotag($object);
        }
    }

    /**
     * Handle storing Flickr-style geo tags to org.routamc.positioning
     * storage should be to org_routamc_positioning_location_dba object
     * with relation org_routamc_positioning_location_dba::RELATION_IN
     *
     * @param net_nemein_tag_link_dba $link
     */
    private function _geotag(net_nemein_tag_link_dba $link)
    {
        // Get all "geo" tags of the object
        $object = midcom::get()->dbfactory->get_object_by_guid($link->fromGuid);
        $geotags = net_nemein_tag_handler::get_object_machine_tags_in_context($object, 'geo');

        $position = [
            'longitude' => null,
            'latitude'  => null,
            'altitude'  => null,
        ];

        foreach ($geotags as $key => $value) {
            switch ($key) {
                case 'lon':
                case 'lng':
                case 'long':
                    $position['longitude'] = $value;
                    break;

                case 'lat':
                    $position['latitude'] = $value;
                    break;

                case 'alt':
                    $position['altitude'] = $value;
                    break;
            }
        }

        if (   is_null($position['longitude'])
            || is_null($position['latitude'])) {
            // Not enough information for positioning, we need both lon and lat
            return false;
        }

        $object_location = new org_routamc_positioning_location_dba();
        $object_location->relation = org_routamc_positioning_location_dba::RELATION_IN;
        $object_location->parent = $link->fromGuid;
        $object_location->parentclass = $link->fromClass;
        $object_location->parentcomponent = $link->fromComponent;
        $object_location->date = $link->metadata->published;
        $object_location->longitude = $position['longitude'];
        $object_location->latitude = $position['latitude'];
        $object_location->altitude = $position['altitude'];

        $object_location->create();
    }
}
