PHP Dependency Injector
-----------------------

A basic autowiring / autoloading injector with good support for argument overriding. 



The injector supports constructor injection and function argument injection:

    // create an instance
    $injector->instantiate(MyClass::class, [
        '$timeout' => 1000
    ]);
    
    // call a function
    $injector->invoke([$object, 'method']);

If an argument is type-hinted as a class, the injector automatically creates 
an instance of this class. 

To control how and which values will be injected, you can create 
aliases, default parameters, singletons and decorators.


##### Alias

    // configure injector to provide an instance of MyImplementation 
    // for every MyInterface 
    $injector->alias(MyInterface::class, MyImplementation::class); 

    class Test {
        function __construct(MyInterface $my) {
            print "Test constructed with " . get_class($my);
        }
    }

##### Singleton

    // configure injector to create only one instance
    $injector->singleton(MyImplementation::class)
    
    // prints "bool(true)" 
    $b = $injector->instantiate(Test::class);
    $a = $injector->instantiate(Test::class);
    var_dump($a === $b); 


##### Decorators

    // configure injector to call the decorator function 
    // after the object is created
    $injector->decorate(Service::class, function(Service $service, Logger $logger){
        $service->logger = $logger;
    });
    
    $service = $injector->instantiate(Service::class);
    $service->logger; // is set


##### Parameters

    // set a parameter value by name
    $injector->defaults(Service::class, [
        '$timeout' => 1000
    ]);

    // set a value for a type-hinted argument
    $injector->defaults(Test::class, [
        MyInterface::class, new MyService()
    ]);

    // set an alias for a type-hinted argument
    $injector->defaults(Test::class, [
        MyInterface::class, MyService::class
    ]);

    // set an alias for a type-hinted argument
    $injector->defaults(Test::class, [
        MyInterface::class, MyService::class
    ]);

    // provide a type hint for an untyped argument
    $injector->defaults(Test::class, [
        'hint $mixed', MyService::class
    ]);

    // set a rest-parameter
    $injector->defaults(Service::class, [
        '...$restParam' => [1,2,3]
    ]):

    // set a parameters array
    $injector->defaults(Service::class, [1000, "hello"]):

    // set a parameter value by index
    $injector->defaults(Service::class, [
        '#0' => 1000
    ]);

Parameter configuration do not have to be complete. 
It is possible to provide or override arguments later:

    $injector->instantiate(Service::class, [
        '$timeout' => 1000
    ]);

    $injector->invoke([$myService, 'method'], [
        '$foo' => 'bar', 
        'hint $other' => Service::class
    ]);

##### Error handling

Parameter configurations, aliases and decorators are strictly validated.

- If you provide undefined parameters, and exception is thrown. 
- If your type hint is not assignable to an existing type hint, an exception is thrown.
- If your alias is not assignable, an exception is thrown. 
- If you alias or otherwise configure a class that has already been instantiated as a singleton, an exception is thrown. 
- If you add an alias that for a class that you have decorated (the decorator would never activate), an exception ist thrown.

