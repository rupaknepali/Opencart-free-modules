<?php
// Heading
$_['heading_title']        = 'AI Shop Assistant';

// Text
$_['text_extension']       = 'Extensions';
$_['text_success']         = 'Success: You have modified AI Shop Assistant!';
$_['text_edit']            = 'Edit AI Shop Assistant';
$_['text_settings']        = 'Settings';
$_['text_questions']       = 'Questions';
$_['text_statistics']      = 'Last 30 days: %s questions in %s conversations, %s input and %s output tokens. Check the cost in the Claude Console under Usage.';
$_['text_info']            = 'Adds a chat button to every page. Claude answers questions by searching your catalog with two read-only tools (product search and product details). It can\'t see customers, carts or orders.';
$_['text_questions_info']  = 'What visitors asked and which products were shown. Questions that returned no products show what people want and you don\'t sell, or can\'t find.';
$_['text_cost']            = 'Each question is one to five API requests, billed by Anthropic to your API key. Claude Opus 5.5 answers best; Claude Sonnet 5.5 and Claude Haiku 5.5 cost less per question. Set limits below and check usage in the Claude Console.';
$_['text_privacy']         = 'Visitor questions and your product data are sent to Anthropic\'s API, and questions are stored in your database for the number of days below. Mention this in your privacy policy.';
$_['text_none']            = 'none';

// Column
$_['column_date']          = 'Date';
$_['column_question']      = 'Question';
$_['column_products']      = 'Products shown';
$_['column_conversation']  = 'Conversation';
$_['column_tokens']        = 'Tokens';

// Entry
$_['entry_status']         = 'Status';
$_['entry_api_key']        = 'Anthropic API key';
$_['entry_model']          = 'Model';
$_['entry_title']          = 'Chat title';
$_['entry_welcome']        = 'Welcome message';
$_['entry_color']          = 'Color';
$_['entry_facts']          = 'Store facts';
$_['entry_session_limit']  = 'Questions per visitor per hour';
$_['entry_ip_limit']       = 'Questions per IP address per hour';
$_['entry_retention']      = 'Keep questions for (days)';

// Help
$_['help_api_key']         = 'Create one at console.anthropic.com under API Keys. Set a monthly spend limit there too.';
$_['help_facts']           = 'Shipping costs and times, return policy, opening hours, contact details. The assistant uses these to answer non-product questions. Keep it short and factual.';
$_['help_limits']          = 'Protects your API bill from abuse. Visitors who reach a limit are asked to try again later.';

// Error
$_['error_warning']        = 'Warning: Please check the form carefully for errors!';
$_['error_permission']     = 'Warning: You do not have permission to modify AI Shop Assistant!';
$_['error_api_key']        = 'Anthropic API keys start with sk-ant-';
$_['error_no_key']         = 'Add an API key before switching the assistant on!';
