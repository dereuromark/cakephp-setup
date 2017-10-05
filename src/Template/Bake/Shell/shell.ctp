<?php
namespace <%= $namespace %>\Shell;

use Cake\Console\Shell;

class <%= $name %>Shell extends Shell
{

    /**
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        return $parser;
    }

    /**
     * @return bool|int|null Success or error code.
     */
    public function main()
    {
        $this->out($this->OptionParser->help());
    }
}
