<?php
/*
 * Cấu hình chatbot AI.
 * Tạo API key tại OpenAI Platform rồi dán vào api_key bên dưới.
 * Không đặt API key trong JavaScript hoặc gửi công khai trên trình duyệt.
 */
return [
    'api_key' => getenv('OPENAI_API_KEY') ?: '',
    'model' => getenv('OPENAI_MODEL') ?: 'gpt-5-mini',
];
