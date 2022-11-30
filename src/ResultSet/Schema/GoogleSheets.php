<?php

namespace datadistillr\Drill\ResultSet\Schema;

use datadistillr\Drill\ResultSet\Schema;

/**
 * Class Google Sheets Schema
 * @package thedataist\Drill
 * @author Ben Stewart <ben@datadistillr.com>
 */
class GoogleSheets extends Schema
{
    // region Properties
    /**
     * Title of Google Sheets file
     * @var string $title
     */
    public string $title;
    // endregion

    /**
     * Schema constructor.
     * @param object|array $data
     */
    public function __construct($data = null)
    {
        parent::__construct($data);

        $data = (object)$data;

        $this->title = $data->title;
    }
}
