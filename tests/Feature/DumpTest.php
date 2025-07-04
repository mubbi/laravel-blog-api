<?php

arch('Test and confirm no dumps or dd used in code to avoid data leaks in prod')
    ->expect(['dd', 'dump', 'exit', 'die', 'print_r', 'var_dump', 'echo', 'print'])
    ->not
    ->toBeUsed();
