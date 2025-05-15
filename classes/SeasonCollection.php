<?php namespace Ladylain\Season\Classes;

use October\Rain\Database\Collection;

/**
 * SeasonCollection is a collection of seasons
 *
 * @package ladylain\season
 * @author Lucas Palomba
 */
class SeasonCollection extends Collection
{
    /**
     * isPrimary
     */
    public function isPrimary()
    {
        return $this->where('is_primary', true);
    }

    /**
     * isEnabled
     */
    public function isEnabled()
    {
        return $this->where('active', true);
    }

    /**
     * isEnabledEdit
     */
    public function isEnabledEdit()
    {
        return $this->where('is_enabled_edit', true);
    }

    /**
     * inGroup
     */
    public function inGroup($groupId = null)
    {
        if (!$groupId) {
            return $this;
        }

        return $this->where('group_id', $groupId);
    }

    /**
     * inLocale
     */
    public function inLocale($localeCode = null)
    {
        if (!$localeCode) {
            return new static;
        }

        return $this->filter(function($site) use ($localeCode) {
            return $site->matchesLocale($localeCode);
        });
    }


}
