<?php

namespace ConnectHolland\TulipAPI;

/**
 * ResponseCodes.
 *
 * @author Niels Nijens <niels@connectholland.nl>
 */
final class ResponseCodes
{
    /**
     * Returned when the API call was successfully processed.
     *
     * @var int
     */
    const SUCCESS = 1000;

    /**
     * Returned when not authenticated / authorized correctly.
     *
     * @var int
     */
    const NOT_AUTHORIZED = 1001;

    /**
     * Returned when the called API service is not found within the Tulip API.
     *
     * @var int
     */
    const UNKNOWN_SERVICE = 1003;

    /**
     * Returned when the required parameters for the service / action were not provided or incorrect.
     *
     * @var int
     */
    const PARAMETERS_REQUIRED = 1004;

    /**
     * Returned when an requested object was not found.
     *
     * @var int
     */
    const NON_EXISTING_OBJECT = 1005;

    /**
     * Returned when an (unknown) error occurs within the Tulip API.
     *
     * @var int
     */
    const UNKNOWN_ERROR = 0;
}
