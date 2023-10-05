<?php

return array(
    'hint_top' => array(
        'title'        => _wp('Tag management'),
        'control_type' => waHtmlControl::CUSTOM.' shopTageditorPlugin::settingsTopHint',
    ),
    'redirect' => array(
        'title'        => _wp('Redirect to custom URLs'),
        'description'  => _wp('Enable 301 redirect from original to custom tag URLs'),
        'control_type' => waHtmlControl::CHECKBOX,
        'value'        => 1,
    ),
    'whitespace_redirect' => array(
        'control_type' => waHtmlControl::RADIOGROUP,
        'title' => _wp('Redirect tags with whitespaces in URLs'),
        'description' => _wp('If a tag contains whitespace characters, its URL by default may contain either <tt>%20</tt> (whitespaces) or <tt>+</tt> (plus signs) in place of whitespaces.')
            .' '
        ._wp('Both URLs of the same tag are valid and display the same tag, which may cause sanctions from search engines for publishing non-unique content at different URLs.')
        .' '
        ._wp('To avoid sanctions, select the plugin to redirect, with 301 response code, from one URL type to another.')
        .' '
        ._wp('If you do not know which of the redirects to choose, simply choose the one you personally like more, or any one at all.'),
        'options' => array(
            array(
                'value' => 0,
                'title' => _wp('do not redirect'),
                'descripion' => _wp(''),
            ),
            array(
                'value' => 'whitespace',
                'title' => _wp('redirect from URLs with whitespaces to URLs with “+” characters'),
                'descripion' => _wp(''),
            ),
            array(
                'value' => 'plus',
                'title' => _wp('redirect from URLs with “+” characters to URLs with whitespaces'),
                'descripion' => _wp(''),
            ),
        ),
        'value' => 0,
    ),
    'seo_first_page'   => array(
        'title'        => _wp('Show SEO text on first page only'),
        'description'  => _wp('Disable SEO text on 2nd and further navigation pages of long tagged product lists.'),
        'control_type' => waHtmlControl::CHECKBOX,
        'value'        => 1,
    ),
    'seo_text_location' => array(
        'title'        => _wp('SEO text location'),
        'control_type' => waHtmlControl::RADIOGROUP,
        'options'      => array(
            array(
                'value'       => 'hook',
                'title'       => _wp('Default place provided by design theme'),
                'description' => _wp('Usually above product list.<br>'),
            ),
            array(
                'value'       => 'manual',
                'title'       => _wp('I will manually add plugin call to a design theme template'),
                'description' => _wp('Add <strong><tt>{shopTageditorPlugin::seoText()}</tt></strong> to the desired design template file; e.g., to <tt>search.html</tt></strong>.'),
            ),
        ),
        'value'        => 'hook',
    ),
    'sort_products' => array(
        'title'        => _wp('Products sort order'),
        'description'  => _wp('How products must be sorted on tag-viewing page in the storefront.').'<br>'
            ._wp('This common setting can be changed individually for each tag.'),
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            'name asc'             => _wp('By name'),
            'price desc'           => _wp('Most expensive'),
            'price asc'            => _wp('Least expensive'),
            'rating desc'          => _wp('Highest rated'),
            'rating asc'           => _wp('Lowest rated'),
            'total_sales desc'     => _wp('Best sellers'),
            'total_sales asc'      => _wp('Worst sellers'),
            'count desc'           => _wp('In stock'),
            'create_datetime desc' => _wp('Date added'),
            'stock_worth desc'     => _wp('Stock net worth'),
        ),
    ),
    'header_sitemap' => array(
        'control_type' => waHtmlControl::CUSTOM.' shopTageditorPlugin::settingsHeaderSitemap',
    ),
    'add_sitemap' => array(
        'title'        => _wp('Add tag URLs to Sitemap file'),
        'control_type' => waHtmlControl::CHECKBOX,
        'description' => '<br>',
    ),
    'sitemap_tag_selection' => array(
        'control_type' => waHtmlControl::RADIOGROUP,
        'title' => _wp('How to add tags to a Sitemap file'),
        'options' => array(
            array(
                'value' => 'all',
                'title' => _wp('Always add all tags, use current time as “Last Modified” value'),
                'description' => _wp('Fastest option. Some tags may be empty if some products are not visible or if different types of products are displayed in different storefronts.'),
            ),
            array(
                'value' => 'cloud_index',
                'title' => _wp('Add only tags linked to visible products, use current time as “Last Modified” value'),
                'description' => sprintf(
                    _wp('More precise option. To use it, either enable “%s” setting, or be sure to regularly update the tag cloud index.'),
                    _wp('Auto-update of main tag cloud')
                ),
            ),
            array(
                'value' => 'products_update_time',
                'title' => _wp('Add only tags linked to visible products, use products’ update time as “Last Modified” value.'),
                'description' => sprintf(
                    _wp('Most precise but slowest option.')
                ),
            ),
        ),
        'value' => 'simple',
    ),
    'custom_sitemap_url' => array(
        'title'        => _wp('Use a separate Sitemap file for tags'),
        'control_type' => waHtmlControl::CUSTOM.' shopTageditorPlugin::settingsSitemapCustomUrl',
    ),
    'sitemap_changefreq' => array(
        'title'        => _wp('Change frequency (changefreq) of tag entries in Sitemap file'),
        'description'  => '<br><br>',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            'daily'   => trim(sprintf('daily %s', _wp('(daily)'))),
            'weekly'  => trim(sprintf('weekly %s', _wp('(weekly)'))),
            'monthly' => trim(sprintf('monthly %s', _wp('(monthly)'))),
        ),
        'value'        => 'weekly',
    ),
    'sitemap_priority' => array(
        'title'        => _wp('Priority percentage (priority) of tag entries in Sitemap file'),
        'control_type' => waHtmlControl::INPUT,
        'description'  => '<br><br>',
        'value'        => '60',
    ),
    'header_shop_cloud' => array(
        'control_type' => waHtmlControl::CUSTOM.' shopTageditorPlugin::settingsHeaderShopCloud',
    ),
    'hint_shop_cloud' => array(
        'control_type' => waHtmlControl::CUSTOM.' shopTageditorPlugin::settingsHintShopCloud',
    ),
    'shop_cloud_auto_update' => array(
        'control_type' => waHtmlControl::CHECKBOX,
        'title'       => _wp('Auto-update of main tag cloud'),
        'description' => sprintf(
            _wp('Automatically update tag cloud index when products’ status, types, and tags are changed in “<a href="%s">Products</a>” section’s listings.'),
            '?action=products'
        ).
            '<br>'.
            _wp('You may disable the auto-update if you do not use this functionality of “Tag editor” plugin.').
            ' '.
            sprintf(
                _wp('And you can update the tag index at any time manually in “<a href="%s">Products → Tag editor</a>” section.'),
                '?action=products#/tageditor/'
            ),
        'value'       => 1,
    ),
    'header_custom_cloud' => array(
        'control_type' => waHtmlControl::CUSTOM.' shopTageditorPlugin::settingsHeaderCustomCloud',
    ),
    'hint_custom_cloud' => array(
        'title' => _wp('How to use'),
        'control_type' => waHtmlControl::CUSTOM.' shopTageditorPlugin::settingsHintCustomCloud',
    ),
    'custom_cloud_urls' => array(
        'title' => _wp('Custom cloud tag links'),
        'description' => _wp('What product listings custom cloud links should point to'),
        'control_type' => waHtmlControl::RADIOGROUP,
        'options' => array(
            'subcollection'=> _wp('show products only from displayed product listing page'),
            'default'=> _wp('show products from entire product catalog'),
        ),
        'value' => 'default',
    ),
    'custom_cloud_show_all' => array(
        'control_type' => waHtmlControl::CHECKBOX,
        'title'        => _wp('Show custom cloud on non-standard product listing pages'),
        'description'  => _wp('Custom tag cloud added by means of <strong><tt>{shopTageditorPlugin::cloud()}</tt></strong> helper shows only tags associated with products'
            .' displayed on a <em>category</em>, <em>search</em>, or <em>tag</em> page.')
            .'<br><br>'
            ._wp('Other product listings pages; e.g., those generated by other plugins or info pages with embedded product sets, are not recognized.'
            .' Please choose for the custom cloud either to show <em>all tags</em> or to show <em>no tags</em> at all on such pages.')
            .'<br><br>'
            ._wp('On the <em>home page</em>, the cloud will always show all tags associated with all products available in the current storefront'
            .' regardless of this setting‘s value.'),
    ),
);
