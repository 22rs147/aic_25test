<?php
namespace aic\views;

class View
{
    protected $view_dir;
    protected $data = [];
    private $header = 'bs4_header.php';
    private $footer = 'bs4_footer.php';

    public function __construct(string $view_dir)
    {
        $this->view_dir = $view_dir;
    }

    public function assign(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    // ヘッダーとフッターを含めたファイルをレンダリング
    public function render(string $template_file, array $data = []): void
    {
        $data = array_merge($this->data, $data);

        ob_start();

        extract($data);

        // Include header
        $header_path = $this->view_dir . $this->header;
        if (file_exists($header_path)) {
            include $header_path;
        } else {
            die("Header file not found: " . htmlspecialchars($header_path));
        }

        // Include content
        $this->renderPartial($template_file, $data);

        // Include footer
        $footer_path = $this->view_dir . $this->footer;
        if (file_exists($footer_path)) {
            include $footer_path;
        } else {
            die("Footer file not found: " . htmlspecialchars($footer_path));
        }

        ob_end_flush();
    }

    // 指定したファイルのみをレンダリング
    public function renderPartial(string $template_file, array $data = []): void
    {
        $data = array_merge($this->data, $data);
        extract($data);
        $file_path = $this->view_dir . $template_file;
        if (file_exists($file_path)) {
            include $file_path;
        } else {
            die("View file not found: " . htmlspecialchars($file_path));
        }
    }

    // レンダリングをせず、リダイレクト
    public function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit();
    }
}