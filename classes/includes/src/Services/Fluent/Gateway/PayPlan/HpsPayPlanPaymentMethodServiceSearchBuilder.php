<?php

/**
 * @method HpsPayPlanPaymentMethodServiceSearchBuilder withData(array $data)
 */
class HpsPayPlanPaymentMethodServiceSearchBuilder extends HpsBuilderAbstract
{
    /** @var array */
    protected $data = array();

    /**
     * Instatiates a new HpsPayPlanPaymentMethodServiceSearchBuilder
     *
     * @param HpsRestGatewayService $service
     */
    public function __construct(HpsRestGatewayService $service)
    {
        parent::__construct($service);
        $this->setUpValidations();
    }

    /**
     * Creates an search transaction through the HpsPayPlanPaymentMethodService
     */
    public function execute()
    {
        parent::execute();

        $service = new HpsPayPlanPaymentMethodService($this->service->servicesConfig());
        $usableData = array_intersect_key(
            $this->data,
            array_flip(HpsPayPlanPaymentMethod::getSearchableFields())
        );

        return $service->search($usableData);
    }

    /**
     * Setups up validations for building payment method
     * searches.
     *
     * @return null
     */
    private function setUpValidations()
    {
        $this
            ->addValidation(array($this, 'dataNotEmpty'), 'HpsArgumentException', 'Search needs a non-empty data set');
    }

    /**
     * Ensures the data set is not empty.
     *
     * @param array $actionCounts
     *
     * @return bool
     */
    protected function dataNotEmpty($actionCounts)
    {
        return !empty($this->data);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return HpsPayPlanPaymentMethodServiceSearchBuilder
     */
    public function filter($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     *
     * @return HpsPayPlanPaymentMethodServiceSearchBuilder
     */
    public function ignore($key)
    {
        return $this->filter($key, null);
    }
}
