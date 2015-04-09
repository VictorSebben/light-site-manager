<?php
if ( $this->user ) {
    echo "<h1>It's here</h1>";
    echo '<h1>Hello, ', $this->user->name, '</h1>';
    //H::ppr( $this->user );
}
else
    echo "User not found :(";
