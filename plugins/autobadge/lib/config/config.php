<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
return array(
    /* Ribbon 1 */
    'ribbon-1' => array(
        'id' => 'ribbon-1',
        'construction' => '<div class="autobadge-pl ribbon-1"><span class="badge-dashed-line"><span class="badge-text-block"><span>' . _wp('Ribbon text') . '</span></span></span></div>',
        'background' => array(
            'type' => 'gradient',
            'gradient' => array(
                'start' => 'ff0000',
                'end' => '990000',
                'orientation' => 'radial',
                'type' => 'to_bottom'
            ),
            'elements' => array(
                ':before' => 'border-top-color',
                ':after' => 'border-top-color',
            )
        ),
        'box-shadow' => array(
            'x-offset' => 0,
            'y-offset' => 0,
            'blur' => 0,
            'spread' => 0,
            'color' => '000000',
            'inset' => 0,
        ),
        'size' => array(
            'type' => 'input',
            'width' => '70',
            'height' => '140',
            'width_ratio' => '0.5',
            'ratio' => array(
                ':before' => array(
                    'border-right' => 'transparent',
                ),
                ':after' => array(
                    'border-left' => 'transparent',
                ),
            )
        ),
        'position' => array(
            'value' => 'top_right',
            'margins' => array(
                'top' => 0,
                'right' => 0,
                'bottom' => 0,
                'left' => 0,
            )
        ),
        'orientation' => 'top_bottom',
        'orientations' => array(
            'right_left' => array(
                'id' => 'ribbon-1-rl',
            ),
            'left_right' => array(
                'id' => 'ribbon-1-lr',
            ),
            'top_bottom' => array(
                'id' => 'ribbon-1-tb'
            ),
            'bottom_top' => array(
                'id' => 'ribbon-1-bt'
            )
        ),
        'torientation' => 'vertical',
        'additional' => array(
            'dashed_line' => array(
                'type' => 'color',
                'color' => '000000',
            ),
            'radius' => array(
                'value' => array(
                    'top-right' => 0,
                    'top-left' => 0,
                ),
            ),
            'tail' => array(
                'type' => 'hide',
                'code' => '<span class="autobadge-pl-tail"></span>',
                'position' => 'top_right',
                'avail_position' => array('top_right', 'top_left'),
                'color' => '263746',
                'size' => '6'
            ),
            'tongue' => array(
                'size' => '20',
                'elements' => array(
                    ':before' => array('bottom' => 'border-top-width'),
                    ':after' => array('bottom' => 'border-top-width'),
                )
            )
        ),
        'multiline' => 1,
        'text' => array(
            array(
                'type' => 'text',
                'value' => _wp('Ribbon text'),
                'color' => 'ffffff',
                'shadow' => 1,
                'family' => 'Arial,sans-serif',
                'style' => 'bold',
                'size' => 14,
                'align' => 'center',
                'width' => 'auto',
                'margins' => array(
                    'top' => 0,
                    'right' => 0,
                )
            )
        ),
    ),
    'ribbon-1-rl' => array(
        'id' => 'ribbon-1-rl',
        'background' => array(
            'gradient' => array(
                'type' => 'to_left'
            ),
            'elements' => array(
                ':before' => 'border-right-color',
                ':after' => 'border-right-color',
            )
        ),
        'size' => array(
            'width_ratio' => '0',
            'height_ratio' => '0.5',
            'ratio' => array(
                ':before' => array(
                    'border-bottom' => 'transparent',
                ),
                ':after' => array(
                    'border-top' => 'transparent',
                ),
            )
        ),
        'additional' => array(
            'radius' => array(
                'value' => array(
                    'top-right' => 0,
                    'bottom-right' => 0,
                ),
            ),
            'tail' => array(
                'avail_position' => array('top_right', 'bottom_right'),
            ),
            'tongue' => array(
                'elements' => array(
                    ':before' => array('left' => 'border-right-width'),
                    ':after' => array('left' => 'border-right-width'),
                )
            )
        )
    ),
    'ribbon-1-lr' => array(
        'id' => 'ribbon-1-lr',
        'background' => array(
            'gradient' => array(
                'type' => 'to_right'
            ),
            'elements' => array(
                ':before' => 'border-left-color',
                ':after' => 'border-left-color',
            )
        ),
        'size' => array(
            'width_ratio' => '0',
            'height_ratio' => '0.5',
            'ratio' => array(
                ':before' => array(
                    'border-bottom' => 'transparent',
                ),
                ':after' => array(
                    'border-top' => 'transparent',
                ),
            )
        ),
        'additional' => array(
            'radius' => array(
                'value' => array(
                    'top-left' => 0,
                    'bottom-left' => 0,
                ),
            ),
            'tail' => array(
                'avail_position' => array('top_left', 'bottom_left'),
            )
        ),
        'tongue' => array(
            'elements' => array(
                ':before' => array('right' => 'border-left-width'),
                ':after' => array('right' => 'border-left-width'),
            )
        )
    ),
    'ribbon-1-tb' => array(
        'id' => 'ribbon-1',
        'background' => array(
            'gradient' => array(
                'type' => 'to_bottom'
            ),
            'elements' => array(
                ':before' => 'border-top-color',
                ':after' => 'border-top-color',
            )
        ),
        'size' => array(
            'height_ratio' => '0',
            'width_ratio' => '0.5',
            'ratio' => array(
                ':before' => array(
                    'border-right' => 'transparent',
                ),
                ':after' => array(
                    'border-left' => 'transparent',
                ),
            )
        ),
        'additional' => array(
            'radius' => array(
                'value' => array(
                    'top-right' => 0,
                    'top-left' => 0,
                ),
            ),
            'tail' => array(
                'avail_position' => array('top_left', 'top_right'),
            ),
            'tongue' => array(
                'elements' => array(
                    ':before' => array('bottom' => 'border-top-width'),
                    ':after' => array('bottom' => 'border-top-width'),
                )
            )
        )
    ),
    'ribbon-1-bt' => array(
        'id' => 'ribbon-1-bt',
        'background' => array(
            'gradient' => array(
                'type' => 'to_top'
            ),
            'elements' => array(
                ':before' => 'border-bottom-color',
                ':after' => 'border-bottom-color',
            )
        ),
        'size' => array(
            'height_ratio' => '0',
            'width_ratio' => '0.5',
            'ratio' => array(
                ':before' => array(
                    'border-right' => 'transparent',
                ),
                ':after' => array(
                    'border-left' => 'transparent',
                ),
            )
        ),
        'additional' => array(
            'radius' => array(
                'value' => array(
                    'bottom-left' => 0,
                    'bottom-right' => 0,
                ),
            ),
            'tail' => array(
                'avail_position' => array('bottom_left', 'bottom_right'),
            ),
            'tongue' => array(
                'elements' => array(
                    ':before' => array('top' => 'border-bottom-width'),
                    ':after' => array('top' => 'border-bottom-width'),
                )
            )
        )
    ),
    /* Ribbon 2 */
    'ribbon-2' => array(
        'id' => 'ribbon-2',
        'construction' => '<div class="autobadge-pl ribbon-2"><span class="badge-dashed-line"><span class="badge-text-block"><span>' . _wp('Ribbon text') . '</span></span></span></div>',
        'background' => array(
            'type' => 'gradient',
            'gradient' => array(
                'start' => 'ff0000',
                'end' => '990000',
                'orientation' => 'radial',
                'type' => 'to_bottom'
            ),
            'elements' => array(
                ':before' => 'border-top-color',
            )
        ),
        'size' => array(
            'type' => 'input',
            'width' => '70',
            'height' => '140',
            'width_ratio' => '0.5',
            'ratio' => array(
                ':before' => array(
                    'border-right' => 'transparent',
                    'border-left' => 'transparent',
                ),
            )
        ),
        'box-shadow' => array(
            'x-offset' => 0,
            'y-offset' => 0,
            'blur' => 0,
            'spread' => 0,
            'color' => '000000',
            'inset' => 0,
        ),
        'position' => array(
            'value' => 'top_right',
            'margins' => array(
                'top' => 0,
                'right' => 0,
                'bottom' => 0,
                'left' => 0,
            )
        ),
        'orientation' => 'top_bottom',
        'orientations' => array(
            'right_left' => array(
                'id' => 'ribbon-2-rl',
            ),
            'left_right' => array(
                'id' => 'ribbon-2-lr',
            ),
            'top_bottom' => array(
                'id' => 'ribbon-2-tb'
            ),
            'bottom_top' => array(
                'id' => 'ribbon-2-bt'
            )
        ),
        'torientation' => 'vertical',
        'additional' => array(
            'dashed_line' => array(
                'type' => 'color',
                'color' => '000000',
            ),
            'radius' => array(
                'value' => array(
                    'top-right' => 0,
                    'top-left' => 0,
                ),
            ),
            'tail' => array(
                'type' => 'hide',
                'position' => 'top_right',
                'avail_position' => array('top_right', 'top_left'),
                'color' => '263746',
                'size' => '6'
            ),
            'tongue' => array(
                'size' => '20',
                'elements' => array(
                    ':before' => array('bottom' => 'border-top-width'),
                )
            )
        ),
        'multiline' => 1,
        'text' => array(
            array(
                'type' => 'text',
                'value' => _wp('Ribbon text'),
                'color' => 'ffffff',
                'shadow' => 1,
                'family' => 'Arial,sans-serif',
                'style' => 'bold',
                'size' => 14,
                'align' => 'center',
                'width' => 'auto',
                'margins' => array(
                    'top' => 0,
                    'right' => 0,
                )
            )
        ),
    ),
    'ribbon-2-tb' => array(
        'id' => 'ribbon-2',
        'background' => array(
            'gradient' => array(
                'type' => 'to_bottom'
            ),
            'elements' => array(
                ':before' => 'border-top-color',
            )
        ),
        'size' => array(
            'height_ratio' => '0',
            'width_ratio' => '0.5',
            'ratio' => array(
                ':before' => array(
                    'border-right' => 'transparent',
                    'border-left' => 'transparent',
                ),
            )
        ),
        'additional' => array(
            'radius' => array(
                'value' => array(
                    'top-right' => 0,
                    'top-left' => 0,
                ),
            ),
            'tail' => array(
                'avail_position' => array('top_left', 'top_right'),
            ),
            'tongue' => array(
                'elements' => array(
                    ':before' => array('bottom' => 'border-top-width'),
                )
            )
        )
    ),
    'ribbon-2-rl' => array(
        'id' => 'ribbon-2-rl',
        'background' => array(
            'gradient' => array(
                'type' => 'to_left'
            ),
            'elements' => array(
                ':before' => 'border-right-color',
            )
        ),
        'size' => array(
            'width_ratio' => '0',
            'height_ratio' => '0.5',
            'ratio' => array(
                ':before' => array(
                    'border-bottom' => 'transparent',
                    'border-top' => 'transparent',
                ),
            )
        ),
        'additional' => array(
            'radius' => array(
                'value' => array(
                    'top-right' => 0,
                    'bottom-right' => 0,
                ),
            ),
            'tail' => array(
                'avail_position' => array('top_right', 'bottom_right'),
            ),
            'tongue' => array(
                'elements' => array(
                    ':before' => array('left' => 'border-right-width'),
                )
            )
        )
    ),
    'ribbon-2-lr' => array(
        'id' => 'ribbon-2-lr',
        'background' => array(
            'gradient' => array(
                'type' => 'to_right'
            ),
            'elements' => array(
                ':before' => 'border-left-color',
            )
        ),
        'size' => array(
            'width_ratio' => '0',
            'height_ratio' => '0.5',
            'ratio' => array(
                ':before' => array(
                    'border-bottom' => 'transparent',
                    'border-top' => 'transparent',
                ),
            )
        ),
        'additional' => array(
            'radius' => array(
                'value' => array(
                    'bottom-left' => 0,
                    'top-left' => 0,
                ),
            ),
            'tail' => array(
                'avail_position' => array('bottom_left', 'top_left'),
            ),
            'tongue' => array(
                'elements' => array(
                    ':before' => array('right' => 'border-left-width'),
                )
            )
        )
    ),
    'ribbon-2-bt' => array(
        'id' => 'ribbon-2-bt',
        'background' => array(
            'gradient' => array(
                'type' => 'to_top'
            ),
            'elements' => array(
                ':before' => 'border-bottom-color',
            )
        ),
        'size' => array(
            'height_ratio' => '0',
            'width_ratio' => '0.5',
            'ratio' => array(
                ':before' => array(
                    'border-right' => 'transparent',
                    'border-left' => 'transparent',
                ),
            )
        ),
        'additional' => array(
            'radius' => array(
                'value' => array(
                    'bottom-right' => 0,
                    'bottom-left' => 0,
                ),
            ),
            'tail' => array(
                'avail_position' => array('bottom_left', 'bottom_right'),
            ),
            'tongue' => array(
                'elements' => array(
                    ':before' => array('top' => 'border-bottom-width'),
                )
            )
        )
    ),
    /* Ribbon 3 */
    'ribbon-3' => array(
        'id' => 'ribbon-3',
        'construction' => '<div class="autobadge-pl ribbon-3"><span class="badge-dashed-line"><span class="badge-text-block"><span>' . _wp('Ribbon text') . '</span></span></span></div>',
        'background' => array(
            'type' => 'gradient',
            'gradient' => array(
                'start' => 'ff0000',
                'end' => '990000',
                'orientation' => 'radial',
                'type' => 'to_bottom'
            ),
        ),
        'border' => array(
            'width' => 0,
            'style' => 'solid',
            'color' => '000000'
        ),
        'size' => array(
            'type' => 'input',
            'width' => '70',
            'max-width' => '',
            'max-height' => '',
            'height' => '140',
            'autowidth' => 1
        ),
        'box-shadow' => array(
            'x-offset' => 0,
            'y-offset' => 0,
            'blur' => 0,
            'spread' => 0,
            'color' => '000000',
            'inset' => 0,
        ),
        'position' => array(
            'value' => 'top_right',
            'margins' => array(
                'top' => 0,
                'right' => 0,
                'bottom' => 0,
                'left' => 0,
            )
        ),
        'orientation' => 'top_bottom',
        'orientations' => array(
            'right_left' => array(
                'id' => 'ribbon-3-rl',
            ),
            'left_right' => array(
                'id' => 'ribbon-3-lr',
            ),
            'top_bottom' => array(
                'id' => 'ribbon-3-tb'
            ),
            'bottom_top' => array(
                'id' => 'ribbon-3-bt'
            )
        ),
        'torientation' => 'vertical',
        'additional' => array(
            'dashed_line' => array(
                'type' => 'color',
                'color' => '000000',
            ),
            'radius' => array(
                'value' => array(
                    'top-right' => 0,
                    'top-left' => 0,
                    'bottom-left' => 0,
                    'bottom-right' => 0,
                ),
            ),
            'tail' => array(
                'type' => 'hide',
                'position' => 'top_right',
                'avail_position' => array('top_right', 'top_left'),
                'color' => '263746',
                'size' => '6'
            )
        ),
        'multiline' => 1,
        'text' => array(
            array(
                'type' => 'text',
                'value' => _wp('Ribbon text'),
                'color' => 'ffffff',
                'shadow' => 1,
                'family' => 'Arial,sans-serif',
                'style' => 'bold',
                'size' => 14,
                'align' => 'center',
                'width' => 'auto',
                'margins' => array(
                    'top' => 0,
                    'right' => 0,
                )
            )
        ),
    ),
    'ribbon-3-tb' => array(
        'id' => 'ribbon-3',
        'background' => array(
            'gradient' => array(
                'type' => 'to_bottom'
            ),
        ),
        'size' => array(
            'height_ratio' => '0',
            'width_ratio' => '0.5',
        ),
        'additional' => array(
            'radius' => array(
                'value' => array(
                    'top-right' => 0,
                    'top-left' => 0,
                    'bottom-left' => 0,
                    'bottom-right' => 0,
                ),
            ),
            'tail' => array(
                'avail_position' => array('top_left', 'top_right'),
            )
        ),
    ),
    'ribbon-3-rl' => array(
        'id' => 'ribbon-3-rl',
        'background' => array(
            'gradient' => array(
                'type' => 'to_left'
            ),
        ),
        'size' => array(
            'width_ratio' => '0',
            'height_ratio' => '0.5',
        ),
        'additional' => array(
            'radius' => array(
                'value' => array(
                    'top-right' => 0,
                    'top-left' => 0,
                    'bottom-left' => 0,
                    'bottom-right' => 0,
                ),
            ),
            'tail' => array(
                'avail_position' => array('top_right', 'bottom_right'),
            )
        ),
    ),
    'ribbon-3-lr' => array(
        'id' => 'ribbon-3-lr',
        'background' => array(
            'gradient' => array(
                'type' => 'to_right'
            ),
        ),
        'size' => array(
            'width_ratio' => '0',
            'height_ratio' => '0.5',
        ),
        'additional' => array(
            'radius' => array(
                'value' => array(
                    'top-right' => 0,
                    'top-left' => 0,
                    'bottom-left' => 0,
                    'bottom-right' => 0,
                ),
            ),
            'tail' => array(
                'avail_position' => array('bottom_left', 'top_left'),
            )
        ),
    ),
    'ribbon-3-bt' => array(
        'id' => 'ribbon-3-bt',
        'background' => array(
            'gradient' => array(
                'type' => 'to_top'
            ),
        ),
        'size' => array(
            'height_ratio' => '0',
            'width_ratio' => '0.5',
        ),
        'additional' => array(
            'radius' => array(
                'value' => array(
                    'top-right' => 0,
                    'top-left' => 0,
                    'bottom-left' => 0,
                    'bottom-right' => 0,
                ),
            ),
            'tail' => array(
                'avail_position' => array('bottom_left', 'bottom_right'),
            )
        ),
    ),
    /* Ribbon 4 */
    'ribbon-4' => array(
        'id' => 'ribbon-4',
        'construction' => '<div class="autobadge-pl ribbon-4"><span class="badge-text-block"><span>' . _wp('Text') . '</span></span></div>',
        'background' => array(
            'type' => 'gradient',
            'element' => 'badge-text-block',
            'gradient' => array(
                'start' => 'ff0000',
                'end' => '990000',
                'orientation' => 'linear',
                'type' => 'to_bottom'
            ),
            'elements' => array(
                ':before' => array('border-top-color', 'border-left-color'),
                ':after' => array('border-top-color', 'border-right-color'),
            )
        ),
        'border' => array(
            'width' => 0,
            'style' => 'solid',
            'color' => '000000'
        ),
        'box-shadow' => array(
            'element' => 'badge-text-block',
            'x-offset' => 0,
            'y-offset' => 3,
            'blur' => 10,
            'spread' => -5,
            'color' => '000000',
            'inset' => 0,
        ),
        'size' => array(
            'type' => 'range',
            'width' => 0,
            'height' => 20,
            'height_element' => array('badge-text-block' => 'line-height'),
            'keys' => array(
                'root' => array('width', 'height'),
                'badge-text-block' => array('width', 'top', 'right'),
            ),
            'values' => array(
                20 => array(
                    array(75, 100, 19, -22),
                    array(80, 106, 21, -23),
                    array(85, 112, 23, -24),
                    array(90, 119, 26, -25),
                    array(95, 126, 28, -26),
                    array(100, 135, 31, -27),
                    array(105, 141, 33, -28),
                    array(110, 148, 36, -29),
                    array(115, 155, 38, -30),
                    array(120, 162, 41, -31),
                    array(125, 170, 44, -32),
                    array(130, 176, 46, -33),
                    array(135, 183, 48, -34),
                    array(140, 190, 51, -35),
                    array(145, 197, 53, -36),
                    array(150, 205, 56, -37),
                ),
                25 => array(
                    array(75, 100, 15, -23),
                    array(80, 106, 17, -24),
                    array(85, 112, 19, -25),
                    array(90, 119, 21, -26),
                    array(95, 126, 23, -27),
                    array(100, 135, 27, -28),
                    array(105, 141, 29, -29),
                    array(110, 148, 32, -30),
                    array(115, 155, 34, -31),
                    array(120, 162, 36, -32),
                    array(125, 170, 39, -34),
                    array(130, 176, 41, -35),
                    array(135, 183, 44, -36),
                    array(140, 190, 46, -37),
                    array(145, 197, 49, -38),
                    array(150, 205, 52, -39),
                ),
                30 => array(
                    array(75, 100, 10, -25),
                    array(80, 106, 12, -26),
                    array(85, 112, 15, -27),
                    array(90, 119, 17, -28),
                    array(95, 126, 19, -29),
                    array(100, 135, 23, -30),
                    array(105, 141, 25, -31),
                    array(110, 148, 27, -32),
                    array(115, 155, 30, -33),
                    array(120, 162, 32, -34),
                    array(125, 170, 35, -35),
                    array(130, 176, 37, -36),
                    array(135, 183, 40, -37),
                    array(140, 190, 42, -38),
                    array(145, 197, 45, -39),
                    array(150, 205, 47, -41),
                ),
                35 => array(
                    array(75, 100, 6, -27),
                    array(80, 106, 8, -28),
                    array(85, 112, 10, -29),
                    array(90, 119, 13, -30),
                    array(95, 126, 15, -31),
                    array(100, 135, 18, -32),
                    array(105, 141, 21, -33),
                    array(110, 148, 23, -34),
                    array(115, 155, 25, -35),
                    array(120, 162, 28, -36),
                    array(125, 170, 31, -37),
                    array(130, 176, 33, -38),
                    array(135, 183, 35, -39),
                    array(140, 190, 38, -40),
                    array(145, 197, 40, -41),
                    array(150, 205, 43, -42),
                ),
                40 => array(
                    array(75, 100, 2, -29),
                    array(80, 106, 4, -30),
                    array(85, 112, 6, -31),
                    array(90, 119, 8, -32),
                    array(95, 126, 11, -33),
                    array(100, 135, 14, -34),
                    array(105, 141, 16, -35),
                    array(110, 148, 19, -36),
                    array(115, 155, 21, -37),
                    array(120, 162, 24, -38),
                    array(125, 170, 26, -39),
                    array(130, 176, 29, -40),
                    array(135, 183, 31, -41),
                    array(140, 190, 34, -42),
                    array(145, 197, 36, -43),
                    array(150, 205, 39, -44),
                ),
                45 => array(
                    array(75, 100, -3, -30),
                    array(80, 106, 0, -31),
                    array(85, 112, 2, -32),
                    array(90, 119, 4, -33),
                    array(95, 126, 7, -34),
                    array(100, 135, 10, -35),
                    array(105, 141, 12, -36),
                    array(110, 148, 14, -37),
                    array(115, 155, 17, -38),
                    array(120, 162, 19, -39),
                    array(125, 170, 22, -41),
                    array(130, 176, 24, -42),
                    array(135, 183, 27, -43),
                    array(140, 190, 29, -44),
                    array(145, 197, 32, -45),
                    array(150, 205, 35, -46),
                ),
                50 => array(
                    array(75, 100, -7, -32),
                    array(80, 106, -5, -33),
                    array(85, 112, -3, -34),
                    array(90, 119, 0, -35),
                    array(95, 126, 2, -36),
                    array(100, 135, 6, -37),
                    array(105, 141, 8, -38),
                    array(110, 148, 10, -39),
                    array(115, 155, 13, -40),
                    array(120, 162, 15, -41),
                    array(125, 170, 18, -42),
                    array(130, 176, 20, -43),
                    array(135, 183, 23, -44),
                    array(140, 190, 25, -45),
                    array(145, 197, 28, -46),
                    array(150, 205, 30, -48),
                ),
            )
        ),
        'position' => array(
            'margins' => array(
                'top' => 0,
                'right' => 0,
            )
        ),
        'orientation' => 'top_right',
        'orientations' => array(
            'top_left' => array(
                'id' => 'ribbon-4-tl',
            ),
            'top_right' => array(
                'id' => 'ribbon-4-tr',
            ),
            'bottom_left' => array(
                'id' => 'ribbon-4-bl'
            ),
            'bottom_right' => array(
                'id' => 'ribbon-4-br'
            )
        ),
        'additional' => array(
            'tails' => array(
                'type' => 'show',
            )
        ),
        'text' => array(
            array(
                'type' => 'text',
                'value' => _wp('Text'),
                'color' => 'ffffff',
                'shadow' => 1,
                'family' => 'Arial,sans-serif',
                'style' => 'bold',
                'size' => 14,
                'align' => 'center',
                'width' => 'auto',
                'margins' => array(
                    'top' => 0,
                    'right' => 0,
                )
            )
        ),
    ),
    'ribbon-4-tl' => array(
        'id' => 'ribbon-4-tl',
        'size' => array(
            'keys' => array(
                'badge-text-block' => array('width', 'top', 'left'),
            ),
        ),
        'background' => array(
            'elements' => array(
                ':before' => array('border-top-color', 'border-left-color'),
                ':after' => array('border-top-color', 'border-right-color'),
            )
        ),
        'position' => array(
            'margins' => array(
                'top' => 0,
                'left' => 0,
            )
        ),
    ),
    'ribbon-4-tr' => array(
        'id' => 'ribbon-4-tr',
        'size' => array(
            'keys' => array(
                'badge-text-block' => array('width', 'top', 'right'),
            ),
        ),
        'background' => array(
            'elements' => array(
                ':before' => array('border-top-color', 'border-left-color'),
                ':after' => array('border-top-color', 'border-right-color'),
            )
        ),
        'position' => array(
            'margins' => array(
                'top' => 0,
                'right' => 0,
            )
        ),
    ),
    'ribbon-4-bl' => array(
        'id' => 'ribbon-4-bl',
        'size' => array(
            'keys' => array(
                'badge-text-block' => array('width', 'bottom', 'left'),
            ),
        ),
        'background' => array(
            'elements' => array(
                ':before' => array('border-top-color', 'border-left-color'),
                ':after' => array('border-top-color', 'border-right-color'),
            )
        ),
        'position' => array(
            'margins' => array(
                'bottom' => 0,
                'left' => 0,
            )
        ),
    ),
    'ribbon-4-br' => array(
        'id' => 'ribbon-4-br',
        'size' => array(
            'keys' => array(
                'badge-text-block' => array('width', 'bottom', 'right'),
            ),
        ),
        'background' => array(
            'elements' => array(
                ':before' => array('border-top-color', 'border-left-color'),
                ':after' => array('border-top-color', 'border-right-color'),
            )
        ),
        'position' => array(
            'margins' => array(
                'bottom' => 0,
                'right' => 0,
            )
        ),
    ),
    /* Ribbon 5 */
    'ribbon-5' => array(
        'id' => 'ribbon-5',
        'construction' => '<div class="autobadge-pl ribbon-5"><span class="badge-dashed-line"><span class="badge-text-block"><span>' . _wp('Ribbon text') . '</span></span></span></div>',
        'background' => array(
            'type' => 'gradient',
            'gradient' => array(
                'start' => 'ff0000',
                'end' => '990000',
                'orientation' => 'radial',
                'type' => 'to_bottom'
            ),
        ),
        'border' => array(
            'width' => 0,
            'style' => 'solid',
            'color' => '000000'
        ),
        'size' => array(
            'type' => 'input',
            'width_percentage' => 1,
            'width' => '110',
            'height' => '70',
        ),
        'box-shadow' => array(
            'x-offset' => 0,
            'y-offset' => 0,
            'blur' => 0,
            'spread' => 0,
            'color' => '000000',
            'inset' => 0,
        ),
        'position' => array(
            'value' => 'top_center',
            'avail_positions' => array('top_center', 'center_center', 'bottom_center'),
            'margins' => array(
                'top' => 0,
                'right' => 0,
                'bottom' => 0,
                'left' => 0,
            )
        ),
        'torientation' => 'horizontal',
        'additional' => array(
            'dashed_line' => array(
                'type' => 'color',
                'color' => '000000',
            ),
            'radius' => array(
                'value' => array(
                    'top-right' => 0,
                    'top-left' => 0,
                    'bottom-left' => 0,
                    'bottom-right' => 0,
                ),
            ),
            'all_tails' => array(
                'type' => 'show',
                'code' => '<span class="autobadge-pl-tail"></span>',
                'position' => array('bottom_left', 'bottom_right'),
                'color' => '263746',
            )
        ),
        'multiline' => 1,
        'text' => array(
            array(
                'type' => 'text',
                'value' => _wp('Ribbon text'),
                'color' => 'ffffff',
                'shadow' => 1,
                'family' => 'Arial,sans-serif',
                'style' => 'bold',
                'size' => 14,
                'align' => 'center',
                'width' => 'auto',
                'margins' => array(
                    'top' => 0,
                    'right' => 0,
                )
            )
        ),
    ),
    /* Ribbon 6 */
    'ribbon-6' => array(
        'id' => 'ribbon-6',
        'construction' => '<div class="autobadge-pl ribbon-6"><span class="badge-text-block"></span></div>',
        'size' => array(
            'type' => 'input',
            'width' => '50',
            'height' => '50',
        ),
        'position' => array(
            'value' => 'top_left',
            'margins' => array(
                'top' => 0,
                'right' => 0,
                'bottom' => 0,
                'left' => 0,
            )
        ),
        'torientation' => 'horizontal',
        'multiline' => 1,
        'text' => array(
            array(
                'type' => 'image',
                'src' => '%plugin_url%img/sale-icon.png',
                'width' => '50'
            )
        ),
    ),
    /* Ribbon 7 */
    'ribbon-7' => array(
        'id' => 'ribbon-7',
        'construction' => '<div class="autobadge-pl ribbon-7"></div>',
        'size' => array(
            'type' => 'input',
            'width' => '',
            'height' => '',
        ),
        'position' => array(
            'value' => 'top_left',
            'margins' => array(
                'top' => 0,
                'right' => 0,
                'bottom' => 0,
                'left' => 0,
            )
        ),
        'text' => array(
            array(
                'type' => 'textarea',
                'content' => '<div class="badge lowprice"><span>' . _wp("Low price") . '</span></div>'
            )
        ),
    ),
);
