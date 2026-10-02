(function () {
    'use strict';

    var states = new WeakMap();

    function destroy(root) {
        var state = states.get(root);
        if (!state) return;

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
        var computed = window.getComputedStyle(root);
        var fontFamily = computed.fontFamily || 'YekanBakh, IRANSans, Tahoma, Arial, sans-serif';
        var trackColor = readCssValue(root, '--ba-impact-chart-track', '#e7eeea');
        var labelBackground = readCssValue(root, '--ba-impact-label-bg', 'rgba(255,255,255,.97)');
        var labelBorder = readCssValue(root, '--ba-impact-label-border', '#dfe8e3');
        var labelNameColor = readCssValue(root, '--ba-impact-label-name', '#485c52');
        var labelWidth = readCssNumber(root, '--ba-impact-label-width', 132);
        var labelRadius = readCssNumber(root, '--ba-impact-label-radius', 13);
        var nameSize = readCssNumber(root, '--ba-impact-label-name-size', 11);
        var valueSize = readCssNumber(root, '--ba-impact-label-value-size', 16);
        var lineLength = readCssNumber(root, '--ba-impact-label-line-length', 8);
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
                overflow: 'truncate'
            }
        };

        sources.forEach(function (source, index) {
            rich['dot' + index] = {
                color: source.color,
                fontFamily: fontFamily,
                fontSize: Math.max(12, valueSize),
                fontWeight: 900,
                lineHeight: Math.round(valueSize * 1.25),
                width: 14,
                align: 'center'
            };
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
                    radius: ['42%', '58%'],
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
                    radius: ['42%', '58%'],
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
                        show: true,
                        position: 'outside',
                        align: 'right',
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
                            var valueText = formatNumber(source.value, decimals) + (unit ? ' ' + unit : '');
                            return '{dot' + index + '|●} {name|' + escapeRichText(source.name) + '}\n{value' + index + '|' + escapeRichText(valueText) + '}';
                        },
                        rich: rich
                    },
                    labelLine: {
                        show: true,
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
            resizeHandler: null
        };

        if ('ResizeObserver' in window) {
            state.resizeObserver = new ResizeObserver(function () {
                if (!chart.isDisposed()) chart.resize();
            });
            state.resizeObserver.observe(chartElement);
        } else {
            state.resizeHandler = function () {
                if (!chart.isDisposed()) chart.resize();
            };
            window.addEventListener('resize', state.resizeHandler);
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
