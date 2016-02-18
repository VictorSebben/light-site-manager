<div class="pagination">
<?php
if ( $this->pagination->getTotalPages() > 1 ) {

    // link for previous page
    if ( ! $this->pagination->hasPreviousPage() ) {
        echo "<span class='pagination-prev-next pagination-disabled'>&lsaquo;&lsaquo; Primeira</span>
              <span class='pagination-prev-next pagination-disabled'>&lsaquo; Anterior</span>";
    } else {
        echo "<a class='pagination-prev-next' href='{$this->pagination->getPagnLink( 1 )}'>&lsaquo;&lsaquo; Primeira</a>
              <a class='pagination-prev-next' href='{$this->pagination->getPagnLink( $this->pagination->getPreviousPage() )}'>&lsaquo; Anterior</a>";
    }

    //$pageNum = $this->pagination->getMinLimit();
    $pageNum = $this->pagination->getMinLimit();
    $end = $pageNum + Pagination::LIM_LINKS * 2;
    // TODO -> IF GETMINLIM > 1 -> SUM LIM TWICE IN THE END
    while ( ( $pageNum <= $this->pagination->getTotalPages() ) && ( $pageNum <= $end ) ) {
        $className = 'pagination-num';

        if ( $pageNum == $this->pagination->getCurrentPage() )
            $className .= ' pagination-current-page';

        echo "<a class='{$className}' href='{$this->pagination->getPagnLink( $pageNum )}'>{$pageNum}</a>";

        $pageNum++;
    }

    // link for next page
    if ( ! $this->pagination->hasNextPage() ) {
        echo "<span class='pagination-prev-next pagination-disabled'>Próxima &rsaquo;</span>
              <span class='pagination-prev-next pagination-disabled'>Última &rsaquo;&rsaquo;</span>";
    } else {
        echo "<a class='pagination-prev-next' href='{$this->pagination->getPagnLink( $this->pagination->getNextPage() )}'>Próxima &rsaquo;</a>
              <a class='pagination-prev-next' href='{$this->pagination->getPagnLink( $this->pagination->getTotalPages() )}'>Última &rsaquo;&rsaquo;</a>";
    }
}
?>
</div>
