import argparse
from multiprocessing import cpu_count

def create_parser():
    parser = argparse.ArgumentParser()
    parser.add_argument('target')
    parser.add_argument('--doc_root', default='/var/www/html')
    parser.add_argument('--all', action="store_true")
    # parser.add_argument('-d', '--debug', action="store_true")
    parser.add_argument('--rabbitmq_ip', default='localhost')
    parser.add_argument('--php_ver', default=5, choices=[5, 7], type=int)
    parser.add_argument('--hook_extension', default="uopz", choices=["uopz", "runkit"])
    parser.add_argument('--cpus', default=cpu_count(), type=int)
    return parser

# def parse(args):
#     parser = create_parser()
#     return parser.parse_args(args)

def parse():
    parser = create_parser()
    return parser.parse_args()
