<?php

/**
 * @see       https://github.com/laminas/laminas-inputfilter for the canonical source repository
 * @copyright https://github.com/laminas/laminas-inputfilter/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-inputfilter/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\InputFilter;

use Laminas\Filter\FilterPluginManager;
use Laminas\InputFilter\InputFilterAbstractServiceFactory;
use Laminas\InputFilter\InputFilterInterface;
use Laminas\InputFilter\InputFilterPluginManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Validator\ValidatorInterface;
use Laminas\Validator\ValidatorPluginManager;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @covers Laminas\InputFilter\InputFilterAbstractServiceFactory
 */
class InputFilterAbstractServiceFactoryTest extends TestCase
{
    /**
     * @var ServiceManager
    */
    protected $services;

    /**
     * @var InputFilterPluginManager
    */
    protected $filters;

    /**
     * @var InputFilterAbstractServiceFactory
     */
    protected $factory;

    public function setUp()
    {
        $this->services = new ServiceManager();
        $this->filters  = new InputFilterPluginManager($this->services);
        $this->services->setService('InputFilterManager', $this->filters);

        $this->factory = new InputFilterAbstractServiceFactory();
    }

    public function testCannotCreateServiceIfNoConfigServicePresent()
    {
        if (method_exists($this->services, 'configure')) {
            // v3
            $method = 'canCreate';
            $args = [$this->getCompatContainer(), 'filter'];
        } else {
            // v2
            $method = 'canCreateServiceWithName';
            $args = [$this->getCompatContainer(), 'filter', 'filter'];
        }
        $this->assertFalse(call_user_func_array([$this->factory, $method], $args));
    }

    public function testCannotCreateServiceIfConfigServiceDoesNotHaveInputFiltersConfiguration()
    {
        $this->services->setService('config', []);
        if (method_exists($this->services, 'configure')) {
            // v3
            $method = 'canCreate';
            $args = [$this->getCompatContainer(), 'filter'];
        } else {
            // v2
            $method = 'canCreateServiceWithName';
            $args = [$this->getCompatContainer(), 'filter', 'filter'];
        }
        $this->assertFalse(call_user_func_array([$this->factory, $method], $args));
    }

    public function testCannotCreateServiceIfConfigInputFiltersDoesNotContainMatchingServiceName()
    {
        $this->services->setService('config', [
            'input_filter_specs' => [],
        ]);
        if (method_exists($this->services, 'configure')) {
            // v3
            $method = 'canCreate';
            $args = [$this->getCompatContainer(), 'filter'];
        } else {
            // v2
            $method = 'canCreateServiceWithName';
            $args = [$this->getCompatContainer(), 'filter', 'filter'];
        }
        $this->assertFalse(call_user_func_array([$this->factory, $method], $args));
    }

    public function testCanCreateServiceIfConfigInputFiltersContainsMatchingServiceName()
    {
        $this->services->setService('config', [
            'input_filter_specs' => [
                'filter' => [],
            ],
        ]);
        if (method_exists($this->services, 'configure')) {
            // v3
            $method = 'canCreate';
            $args = [$this->getCompatContainer(), 'filter'];
        } else {
            // v2
            $method = 'canCreateServiceWithName';
            $args = [$this->getCompatContainer(), 'filter', 'filter'];
        }
        $this->assertTrue(call_user_func_array([$this->factory, $method], $args));
    }

    public function testCreatesInputFilterInstance()
    {
        $this->services->setService('config', [
            'input_filter_specs' => [
                'filter' => [],
            ],
        ]);
        if (method_exists($this->services, 'configure')) {
            // v3
            $method = '__invoke';
            $args = [$this->getCompatContainer(), 'filter'];
        } else {
            // v2
            $method = 'createServiceWithName';
            $args = [$this->getCompatContainer(), 'filter', 'filter'];
        }
        $filter = call_user_func_array([$this->factory, $method], $args);
        $this->assertInstanceOf(InputFilterInterface::class, $filter);
    }

    /**
     * @depends testCreatesInputFilterInstance
     */
    public function testUsesConfiguredValidationAndFilterManagerServicesWhenCreatingInputFilter()
    {
        $filters = new FilterPluginManager($this->services);
        $filter  = function ($value) {
        };
        $filters->setService('foo', $filter);

        $validators = new ValidatorPluginManager($this->services);
        /** @var ValidatorInterface|MockObject $validator */
        $validator  = $this->getMock(ValidatorInterface::class);
        $validators->setService('foo', $validator);

        $this->services->setService('FilterManager', $filters);
        $this->services->setService('ValidatorManager', $validators);
        $this->services->setService('config', [
            'input_filter_specs' => [
                'filter' => [
                    'input' => [
                        'name' => 'input',
                        'required' => true,
                        'filters' => [
                            [ 'name' => 'foo' ],
                        ],
                        'validators' => [
                            [ 'name' => 'foo' ],
                        ],
                    ],
                ],
            ],
        ]);


        if (method_exists($this->services, 'configure')) {
            // v3
            $method = '__invoke';
            $args = [$this->getCompatContainer(), 'filter'];
        } else {
            // v2
            $method = 'createServiceWithName';
            $args = [$this->getCompatContainer(), 'filter', 'filter'];
        }
        $inputFilter = call_user_func_array([$this->factory, $method], $args);
        $this->assertTrue($inputFilter->has('input'));

        $input = $inputFilter->get('input');

        $filterChain = $input->getFilterChain();
        $this->assertSame($filters, $filterChain->getPluginManager());
        $this->assertEquals(1, count($filterChain));
        $this->assertSame($filter, $filterChain->plugin('foo'));
        $this->assertEquals(1, count($filterChain));

        $validatorChain = $input->getValidatorChain();
        $this->assertSame($validators, $validatorChain->getPluginManager());
        $this->assertEquals(1, count($validatorChain));
        $this->assertSame($validator, $validatorChain->plugin('foo'));
        $this->assertEquals(1, count($validatorChain));
    }

    public function testRetrieveInputFilterFromInputFilterPluginManager()
    {
        $this->services->setService('config', [
            'input_filter_specs' => [
                'foobar' => [
                    'input' => [
                        'name' => 'input',
                        'required' => true,
                        'filters' => [
                            [ 'name' => 'foo' ],
                        ],
                        'validators' => [
                            [ 'name' => 'foo' ],
                        ],
                    ],
                ],
            ],
        ]);
        $validators = new ValidatorPluginManager($this->services);
        /** @var ValidatorInterface|MockObject $validator */
        $validator  = $this->getMock(ValidatorInterface::class);
        $this->services->setService('ValidatorManager', $validators);
        $validators->setService('foo', $validator);

        $filters = new FilterPluginManager($this->services);
        $filter  = function ($value) {
        };
        $filters->setService('foo', $filter);

        $this->services->setService('FilterManager', $filters);
        $this->services->get('InputFilterManager')->addAbstractFactory(InputFilterAbstractServiceFactory::class);

        $inputFilter = $this->services->get('InputFilterManager')->get('foobar');
        $this->assertInstanceOf(InputFilterInterface::class, $inputFilter);
    }

    /**
     * Returns appropriate instance to pass to `canCreate()` et al depending on SM version
     *
     * v3 passes the 'creationContext' (ie the root SM) to the AbstractFactory, whereas v2 passes the PluginManager
     */
    protected function getCompatContainer()
    {
        if (method_exists($this->services, 'configure')) {
            // v3
            return $this->services;
        } else {
            // v2
            return $this->filters;
        }
    }
}
