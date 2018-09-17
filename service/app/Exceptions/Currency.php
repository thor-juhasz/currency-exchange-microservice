<?php

namespace App\Exceptions;


/**
 * Class Currency
 *
 * @provides  get_extra_data()  Gives extra exception data for logging context (sentry)
 *
 * @package App\Exceptions
 *
 * @author  Þói Juhasz
 */
class Currency extends \Exception
{
    /** @var array  */
    protected $extra_data = [];

    /**
     * CurrencyException constructor.
     *
     * @param   string           $message     Exception message
     * @param   array            $extra_data  Extra exception data
     * @param   int              $code        Exception code
     * @param   \Throwable|null  $previous    Previous exception, if any
     *
     * @author  Þói Juhasz
     */
    public function __construct(string $message, array $extra_data = [], int $code = 0, \Throwable $previous = null)
    {
        $message = "ERROR: " . $message;
        $this->extra_data = $extra_data;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get extra exception data
     *
     * @return  array
     *
     * @author  Þói Juhasz
     */
    public function get_extra_data(): array
    {
        return $this->extra_data;
    }

    /**
     * String representation of the exception
     *
     * @return  string
     *
     * @author  Þói Juhasz
     */
    public function __toString(): string
    {
        return sprintf(
            "%s in file %s at line %s",
            $this->getMessage(),
            $this->getFile(),
            $this->getLine());
    }
}