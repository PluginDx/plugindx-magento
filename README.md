# PluginDx Framework for Magento 1

If you build extensions or themes for Magento, you know support can be tricky. With PluginDx, you can provide incredible support for your customers and save hours of time:

- Provide support directly inside the Magento admin panel where your configuration lives.
- Reduce ticket volume by giving your customers answers instantly with a built-in knowledge base.
- Eliminate the annoying back-and-forth emails with store diagnostics automatically sent back to you.
- Quickly determine common issues and point them out inside your extension with diagnostic rules.
- Get valuable usage data and support analytics directly inside our app.


## Getting Started

To get started, you'll need to set up PluginDx on your server and add Magento as your first integration. From there, you'll be given instructions on how to add PluginDx to your extension. At a bare minimum, you'll add this code snippet with your integration ID:

```javascript
<script src="https://app.plugindx.com/embed.js" async></script>
<div class="plugindx" data-label="Expedited Support" data-key="YOUR INTEGRATION KEY" data-report="<?php echo Mage::helper('plugindx')->getReportUrl() ?>"></div>
```

This should be placed where you'd like the PluginDx button to be shown to your users. To generate diagnostic reports, you'll need to copy over the code from this framework into your extension.

## How It Works

This extension is a small library to assist PluginDx with providing Magento diagnostics and server configuration info. You'll need to include it with your extension or theme. Out of the box, we'll return high-level data about your customer's Magento store such as the current version, license (CE or EE), and store URL. We'll also return server info such as the PHP version. If you'd like to see more data, you can customize the configuration JSON in your [PluginDx account](https://app.plugindx.com). This JSON configuration allows you to pull the following data from a Magento store:

- Configuration data specific to your extension or theme
- Native configuration data for a Magento store
- Currently installed extensions

We recommend only pulling data that you need to answer support requests. You should be upfront with your customers and tell them explicitly what kind of data you're collecting for support.

You can also write your own custom scripts to get more data. When a request for help is initiated, we'll emit an event `plugindx_framework_report` that can be intercepted by your extension with an observer. In your observer, you can return back data that will be sent through PluginDx.

## Supported Versions

- Magento CE 1.9
- Magento CE 1.8
- Magento CE 1.7
- Magento EE 1.11
- Magento EE 1.10
