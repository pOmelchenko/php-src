--TEST--
Namespace visibility callable cache respects caller namespace
--FILE--
<?php

namespace {
    function nsvis_callable_cache_result(string $label, $value): void {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        echo $label, ': ', $value, "\n";
    }

    function nsvis_callable_cache_report(string $label, callable $callback): void {
        try {
            $value = $callback();
            if ($value instanceof Closure) {
                $value = $value();
            }
            nsvis_callable_cache_result($label, $value);
        } catch (Throwable $e) {
            echo $label, ': ', get_class($e), ': ', $e->getMessage(), "\n";
        }
    }
}

namespace NsVis\CallableCache\Library {
    private(namespace) class Hidden {
        public static function ping(): string {
            return 'hidden';
        }
    }

    $callable = [Hidden::class, 'ping'];
    \nsvis_callable_cache_report('library-is-callable-1', fn() => is_callable($callable));
    \nsvis_callable_cache_report('library-is-callable-2', fn() => is_callable($callable));
    \nsvis_callable_cache_report('library-closure', fn() => \Closure::fromCallable($callable));
}

namespace NsVis\CallableCache\Consumer {
    $callable = ['NsVis\CallableCache\Library\Hidden', 'ping'];
    \nsvis_callable_cache_report('consumer-is-callable-after-allowed', fn() => is_callable($callable));
    \nsvis_callable_cache_report('consumer-closure-after-allowed', fn() => \Closure::fromCallable($callable));
}

namespace NsVis\CallableCache\Library {
    $callable = [Hidden::class, 'ping'];
    \nsvis_callable_cache_report('library-is-callable-after-denied', fn() => is_callable($callable));
}

namespace nsvis\callablecache\library {
    $callable = ['NsVis\CallableCache\Library\Hidden', 'ping'];
    \nsvis_callable_cache_report('case-is-callable', fn() => is_callable($callable));
    \nsvis_callable_cache_report('case-closure', fn() => \Closure::fromCallable($callable));
}

namespace NsVis\CallableCache\Segment {
    protected(namespace) class ProtectedTarget {
        public static function ping(): string {
            return 'segment';
        }
    }
}

namespace NsVis\CallableCache\Segment\Child {
    $callable = ['NsVis\CallableCache\Segment\ProtectedTarget', 'ping'];
    \nsvis_callable_cache_report('segment-child-is-callable', fn() => is_callable($callable));
}

namespace NsVis\CallableCache\SegmentExtra {
    $callable = ['NsVis\CallableCache\Segment\ProtectedTarget', 'ping'];
    \nsvis_callable_cache_report('segment-prefix-is-callable', fn() => is_callable($callable));
    \nsvis_callable_cache_report('segment-prefix-closure', fn() => \Closure::fromCallable($callable));
}

namespace NsVis\CallableCache\Ranges\Library {
    private(namespace) class RangeHidden {
        public static function ping(): string {
            return 'range';
        }
    }

    $callable = [RangeHidden::class, 'ping'];
    \nsvis_callable_cache_report('range-library-1', fn() => is_callable($callable));
}

namespace NsVis\CallableCache\Ranges\Consumer {
    $callable = ['NsVis\CallableCache\Ranges\Library\RangeHidden', 'ping'];
    \nsvis_callable_cache_report('range-consumer', fn() => is_callable($callable));
}

namespace NsVis\CallableCache\Ranges\Library {
    $callable = [RangeHidden::class, 'ping'];
    \nsvis_callable_cache_report('range-library-2', fn() => is_callable($callable));
}

?>
--EXPECT--
library-is-callable-1: true
library-is-callable-2: true
library-closure: hidden
consumer-is-callable-after-allowed: false
consumer-closure-after-allowed: Error: Cannot access private(namespace) class NsVis\CallableCache\Library\Hidden from namespace NsVis\CallableCache\Consumer
library-is-callable-after-denied: true
case-is-callable: true
case-closure: hidden
segment-child-is-callable: true
segment-prefix-is-callable: false
segment-prefix-closure: Error: Cannot access protected(namespace) class NsVis\CallableCache\Segment\ProtectedTarget from namespace NsVis\CallableCache\SegmentExtra
range-library-1: true
range-consumer: false
range-library-2: true
