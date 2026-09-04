<?php
declare(strict_types=1);

namespace {
    $GLOBALS['afe_test_hooks']=[];
    function add_action(string $hook, callable $callback): void { $GLOBALS['afe_test_hooks'][$hook][]=$callback; }

    spl_autoload_register(static function(string $class): void {
        $prefix='BonyadAlavi\\FormEngine\\';
        if (!str_starts_with($class,$prefix)) return;
        $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
        if (is_readable($file)) require_once $file;
    });
}

namespace Elementor {
    class Widget_Base {
        public function __construct(array $data=[], ?array $args=null) {}
        protected function start_controls_section(string $id,array $args=[]): void {}
        protected function end_controls_section(): void {}
        protected function add_control(string $id,array $args=[]): void {}
        protected function get_settings_for_display(): array { return []; }
    }
    class Controls_Manager { public const SELECT='select'; }
}

namespace Elementor\Core\DynamicTags {
    class Tag {
        public function __construct(array $data=[], ?array $args=null) {}
        protected function add_control(string $id,array $args=[]): void {}
        protected function get_settings(string $key): mixed { return null; }
    }
}

namespace Elementor\Modules\DynamicTags {
    class Module { public const TEXT_CATEGORY='text'; }
}

namespace {
    use BonyadAlavi\FormEngine\Elementor\Integration;
    use BonyadAlavi\FormEngine\Form\FormRegistry;
    use BonyadAlavi\FormEngine\Form\Renderer;

    final class WidgetManagerStub {
        public array $registered=[];
        public function register(object $widget): void { $this->registered[]=$widget; }
    }
    final class DynamicManagerStub {
        public array $registered=[];
        public function register(object $tag): void { $this->registered[]=$tag; }
    }

    $registry=new FormRegistry();
    $renderer=(new \ReflectionClass(Renderer::class))->newInstanceWithoutConstructor();
    (new Integration($registry,$renderer))->boot();

    $widgetManager=new WidgetManagerStub();
    foreach($GLOBALS['afe_test_hooks']['elementor/widgets/register']??[] as $callback) $callback($widgetManager);
    $dynamicManager=new DynamicManagerStub();
    foreach($GLOBALS['afe_test_hooks']['elementor/dynamic_tags/register']??[] as $callback) $callback($dynamicManager);

    $widget=$widgetManager->registered[0]??null;
    $tag=$dynamicManager->registered[0]??null;
    $checks=[
        'widget-hook-registered'=>isset($GLOBALS['afe_test_hooks']['elementor/widgets/register']),
        'dynamic-hook-registered'=>isset($GLOBALS['afe_test_hooks']['elementor/dynamic_tags/register']),
        'widget-native-constructor'=>is_object($widget) && $widget::class==='BonyadAlavi\\FormEngine\\Elementor\\FormWidget',
        'dynamic-tag-native-constructor'=>is_object($tag) && $tag::class==='BonyadAlavi\\FormEngine\\Elementor\\FormDynamicTag',
    ];
    foreach($checks as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok) exit(1); }
}
