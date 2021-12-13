import os
FUZZER_DIR = os.path.dirname(os.path.abspath(__file__)) + '/../Fuzzer/fuzz.py'

MAGIC_METHODS = [
                #  '__construct',
                 '__destruct',
                 '__call',
                 '__callStatic',
                 '__get',
                 '__set',
                 '__isset',
                 '__unset',
                 '__sleep',
                 '__wakeup',
                 '__toString',
                #  '__invoke',
                 '__set_state',
                 '__clone',
                #  '__debugInfo'
                ]