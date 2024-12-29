<?php

class Pagination {
    private $totalRecords;
    private $recordsPerPage;
    private $totalPages;
    private $currentPage;
    private $offset;

    public function __construct($totalRecords, $recordsPerPage = 5) {
        $this->totalRecords = $totalRecords;
        $this->recordsPerPage = $recordsPerPage;
        $this->totalPages = ceil($totalRecords / $recordsPerPage);
        $this->currentPage = isset($_GET['page']) ? max(1, min((int)$_GET['page'], $this->totalPages)) : 1;
        $this->offset = ($this->currentPage - 1) * $recordsPerPage;
    }

    public function getOffset() {
        return $this->offset;
    }

    public function getLimit() {
        return $this->recordsPerPage;
    }

    public function getCurrentPage() {
        return $this->currentPage;
    }

    public function getTotalPages() {
        return $this->totalPages;
    }

    public function renderPagination($baseUrl) {
        if ($this->totalPages <= 1) {
            return '';
        }

        $html = '<div class="flex items-center justify-between mt-4">
                    <div class="flex flex-1 justify-between sm:hidden">
                        <a href="' . $this->getPageUrl($baseUrl, max(1, $this->currentPage - 1)) . '" 
                           class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Trước
                        </a>
                        <a href="' . $this->getPageUrl($baseUrl, min($this->totalPages, $this->currentPage + 1)) . '" 
                           class="relative ml-3 inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Sau
                        </a>
                    </div>
                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Hiển thị từ <span class="font-medium">' . (($this->currentPage - 1) * $this->recordsPerPage + 1) . '</span>
                                đến <span class="font-medium">' . min($this->currentPage * $this->recordsPerPage, $this->totalRecords) . '</span>
                                trong tổng số <span class="font-medium">' . $this->totalRecords . '</span> kết quả
                            </p>
                        </div>
                        <div>
                            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">';

        // Previous button
        $html .= '<a href="' . $this->getPageUrl($baseUrl, max(1, $this->currentPage - 1)) . '" 
                    class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                    <span class="sr-only">Previous</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                    </svg>
                 </a>';

        // Page numbers
        for ($i = 1; $i <= $this->totalPages; $i++) {
            if ($i == $this->currentPage) {
                $html .= '<a href="#" aria-current="page" 
                            class="relative z-10 inline-flex items-center bg-blue-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">' . $i . '</a>';
            } else {
                $html .= '<a href="' . $this->getPageUrl($baseUrl, $i) . '" 
                            class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">' . $i . '</a>';
            }
        }

        // Next button
        $html .= '<a href="' . $this->getPageUrl($baseUrl, min($this->totalPages, $this->currentPage + 1)) . '" 
                    class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                    <span class="sr-only">Next</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                    </svg>
                 </a>';

        $html .= '</nav></div></div></div>';

        return $html;
    }

    private function getPageUrl($baseUrl, $page) {
        $url = $baseUrl;
        if (strpos($url, '?') !== false) {
            $url .= '&';
        } else {
            $url .= '?';
        }
        return $url . 'page=' . $page;
    }
}
