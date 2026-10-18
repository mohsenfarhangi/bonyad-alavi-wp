(function () {
    'use strict';

    var states = new WeakMap();

    function destroy(root) {
        var state = states.get(root);
        if (!state) return;

        state.disposed = true;
        cancelAnimationFrame(state.frame);
        state.mobile.removeEventListener('change', state.styleHandler);
        if (document.fonts) document.fonts.removeEventListener('loadingdone', state.styleHandler);
        if (state.resizeObserver) state.resizeObserver.disconnect();
        if (state.resizeHandler) window.removeEventListener('resize', state.resizeHandler);
        if (state.chart && !state.chart.isDisposed()) state.chart.dispose();

        states.delete(root);
    }

    function readCssValue(element, name, fallback) {
        var value = window.getComputedStyle(element).getPropertyValue(name).trim();
        return value || fallback;
    }

    function readCssNumber(element, name, fallback) {
        var value = parseFloat(readCssValue(element, name, ''));
        return Number.isFinite(value) ? value : fallback;
    }

    function formatNumber(value, decimals) {
        return new Intl.NumberFormat('fa-IR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(Number(value) || 0);
    }

    function escapeRichText(value) {
        return String(value == null ? '' : value).replace(/[{}|]/g, '');
    }

    function parsePayload(root) {
        var node = root.querySelector('[data-js-impact-chart-data]');
        if (!node) return null;

        try {
            var payload = JSON.parse(node.textContent || '{}');
            if (!payload || !Array.isArray(payload.sources)) return null;
            return payload;
        } catch (error) {
            return null;
        }
    }

    function buildOption(root, chartElement, payload) {
        var styleSource = chartElement.closest('.ba-impact__funding') || root;
        var mobile = window.matchMedia('(max-width: 760px)').matches;
        var computed = window.getComputedStyle(root);
        var fontFamily = computed.fontFamily || 'YekanBakh, IRANSans, Tahoma, Arial, sans-serif';
        var trackColor = readCssValue(styleSource, '--ba-impact-chart-track', '#e7eeea');
        var labelBackground = readCssValue(styleSource, '--ba-impact-label-bg', 'rgba(255,255,255,.97)');
        var labelBorder = readCssValue(styleSource, '--ba-impact-label-border', '#dfe8e3');
        var labelNameColor = readCssValue(styleSource, '--ba-impact-label-name', '#485c52');
        var labelWidth = readCssNumber(styleSource, '--ba-impact-label-width', 132);
        var labelRadius = readCssNumber(styleSource, '--ba-impact-label-radius', 13);
        var nameSize = readCssNumber(styleSource, '--ba-impact-label-name-size', 11);
        var valueSize = readCssNumber(styleSource, '--ba-impact-label-value-size', 16);
        var lineLength = readCssNumber(styleSource, '--ba-impact-label-line-length', 8);
        var edgeDistance = readCssNumber(styleSource, '--ba-impact-label-edge-distance', 14);
        var decimals = Math.max(0, Math.min(3, Number(payload.decimals) || 0));
        var unit = String(payload.unit || '').trim();
        var sources = payload.sources
            .map(function (source) {
                return {
                    name: String(source.name || '').trim(),
                    value: Math.max(0, Number(source.value) || 0),
                    color: String(source.color || '#06783a')
                };
            })
            .filter(function (source) {
                return source.name || source.value > 0;
            });

        var rich = {
            name: {
                color: labelNameColor,
                fontFamily: fontFamily,
                fontSize: nameSize,
                fontWeight: 500,
                lineHeight: Math.round(nameSize * 1.55),
                width: Math.max(60, labelWidth - 28),
                align: 'right',
                overflow: 'break'
            }
        };

        sources.forEach(function (source, index) {
            rich['value' + index] = {
                color: source.color,
                fontFamily: fontFamily,
                fontSize: valueSize,
                fontWeight: 900,
                lineHeight: Math.round(valueSize * 1.35),
                width: labelWidth,
                align: 'right'
            };
        });

        var data = sources.map(function (source) {
            return {
                name: source.name,
                value: source.value,
                itemStyle: {
                    color: source.color
                },
                labelLine: {
                    lineStyle: {
                        color: source.color,
                        opacity: .62,
                        width: 1
                    }
                }
            };
        });

        var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        return {
            backgroundColor: 'transparent',
            animation: !reduceMotion,
            animationDuration: 850,
            animationDurationUpdate: 350,
            animationEasing: 'cubicOut',
            richInheritPlainLabel: false,
            aria: {
                enabled: true,
                description: sources.map(function (source) {
                    return source.name + ' ' + formatNumber(source.value, decimals) + (unit ? ' ' + unit : '');
                }).join('، ')
            },
            tooltip: {
                show: false
            },
            series: [
                {
                    type: 'pie',
                    silent: true,
                    radius: mobile ? ['60%', '82%'] : ['42%', '58%'],
                    center: ['50%', '50%'],
                    animation: false,
                    label: { show: false },
                    labelLine: { show: false },
                    data: [
                        {
                            value: 1,
                            itemStyle: {
                                color: trackColor
                            }
                        }
                    ],
                    z: 0
                },
                {
                    type: 'pie',
                    radius: mobile ? ['60%', '82%'] : ['42%', '58%'],
                    center: ['50%', '50%'],
                    startAngle: 90,
                    clockwise: true,
                    padAngle: sources.length > 1 ? 2 : 0,
                    avoidLabelOverlap: true,
                    stillShowZeroSum: false,
                    selectedMode: false,
                    minShowLabelAngle: 0,
                    itemStyle: {
                        borderWidth: 0
                    },
                    label: {
                        show: !mobile,
                        position: 'outside',
                        alignTo: 'edge',
                        edgeDistance: edgeDistance,
                        bleedMargin: Math.max(4, Math.round(edgeDistance / 2)),
                        distanceToLabelLine: 3,
                        backgroundColor: labelBackground,
                        borderColor: labelBorder,
                        borderWidth: 1,
                        borderRadius: labelRadius,
                        padding: [8, 10],
                        width: labelWidth,
                        shadowColor: 'rgba(7,76,44,.055)',
                        shadowBlur: 10,
                        shadowOffsetY: 4,
                        fontFamily: fontFamily,
                        formatter: function (params) {
                            var index = params.dataIndex;
                            var source = sources[index];
                            if (!source) return '';
                            var valueText = formatNumber(source.value, decimals);
                            return '{value' + index + '|' + escapeRichText(valueText) + '}\n {name|' + escapeRichText(source.name) + '}';
                        },
                        rich: rich
                    },
                    labelLine: {
                        show: !mobile,
                        length: lineLength,
                        length2: Math.max(4, Math.round(lineLength * .75)),
                        smooth: false
                    },
                    labelLayout: {
                        moveOverlap: 'shiftY',
                        hideOverlap: false
                    },
                    emphasis: {
                        scale: true,
                        scaleSize: 4,
                        itemStyle: {
                            shadowBlur: 8,
                            shadowColor: 'rgba(7,76,44,.16)'
                        }
                    },
                    data: data,
                    z: 1
                }
            ]
        };
    }

    function init(root) {
        if (!root) return;

        destroy(root);

        var chartElement = root.querySelector('[data-js-impact-chart]');
        var payload = parsePayload(root);

        if (!chartElement || !payload || !window.echarts) return;

        var chart = window.echarts.init(chartElement, null, {
            renderer: 'svg'
        });

        chart.setOption(buildOption(root, chartElement, payload), true);

        var state = {
            chart: chart,
            resizeObserver: null,
            resizeHandler: null,
            mobile: window.matchMedia('(max-width: 760px)'),
            frame: 0,
            disposed: false,
            forceStyle: false,
            width: chartElement.clientWidth,
            height: chartElement.clientHeight,
            signature: styleSignature()
        };

        function styleSignature() {
            var source = chartElement.closest('.ba-impact__funding') || root;
            var styles = window.getComputedStyle(source);
            return ['--ba-impact-chart-track', '--ba-impact-label-bg', '--ba-impact-label-border',
                '--ba-impact-label-name', '--ba-impact-label-width', '--ba-impact-label-radius',
                '--ba-impact-label-name-size', '--ba-impact-label-value-size',
                '--ba-impact-label-line-length', '--ba-impact-label-edge-distance']
                .map(function (name) { return styles.getPropertyValue(name); })
                .concat([window.getComputedStyle(root).fontFamily,
                    String(window.matchMedia('(max-width: 760px)').matches),
                    String(window.matchMedia('(prefers-reduced-motion: reduce)').matches)])
                .join('|');
        }

        function refreshChart() {
            if (state.disposed || chart.isDisposed()) return;
            var signature = styleSignature();
            var sizeChanged = state.width !== chartElement.clientWidth || state.height !== chartElement.clientHeight;
            if (sizeChanged) chart.resize();
            if (signature !== state.signature || state.forceStyle) {
                var option = buildOption(root, chartElement, payload);
                option.animationDuration = 0;
                option.animationDurationUpdate = 0;
                chart.setOption(option, true);
            }
            state.width = chartElement.clientWidth;
            state.height = chartElement.clientHeight;
            state.signature = signature;
            state.forceStyle = false;
        }

        function scheduleRefresh(forceStyle) {
            if (state.disposed) return;
            state.forceStyle = state.forceStyle || forceStyle;
            cancelAnimationFrame(state.frame);
            state.frame = requestAnimationFrame(refreshChart);
        }

        state.resizeHandler = function () { scheduleRefresh(false); };
        state.styleHandler = function () { scheduleRefresh(true); };
        window.addEventListener('resize', state.resizeHandler, { passive: true });
        state.mobile.addEventListener('change', state.styleHandler);
        if ('ResizeObserver' in window) {
            state.resizeObserver = new ResizeObserver(state.resizeHandler);
            state.resizeObserver.observe(chartElement);
        }
        if (document.fonts) {
            document.fonts.ready.then(state.styleHandler);
            document.fonts.addEventListener('loadingdone', state.styleHandler);
        }
        states.set(root, state);
    }

    function initDocument() {
        document.querySelectorAll('[data-ba-home-about]').forEach(init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDocument);
    } else {
        initDocument();
    }

    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/bonyad_alavi_home_about.default', function ($scope) {
            init($scope[0].querySelector('[data-ba-home-about]'));
        });
    }
})();
