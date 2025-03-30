import sys
import os
from random import *
from ctypes import *
import logging
import logging.config

def configure_logging():

    # Define the configuration
    LOGGING_CONFIG = {
        'version': 1,
        'disable_existing_loggers': False,  # Don't disable existing loggers
        'formatters': {
            'simple': {
                'format': '%(asctime)s - %(name)s - %(levelname)s - %(message)s',
            },
            'detailed': {
                'format': '%(asctime)s - %(name)s - %(levelname)s - %(message)s - %(filename)s:%(lineno)d',
            },
        },
        'handlers': {
            'console': {
                'class': 'logging.StreamHandler',
                'formatter': 'simple',
                'level': 'DEBUG',
            },
            'file_handler': {
                'class': 'logging.FileHandler',
                'formatter': 'detailed',
                'level': 'INFO',
                'filename': 'app.log',
            },
        },
        'loggers': {
            'main_logger': {  # Main logger
                'handlers': ['console'],
                'level': 'INFO',
                'propagate': False,
            },
            'module_logger': {  # Module logger
                'handlers': ['console'],
                'level': 'WARNING',
                'propagate': False,
            },
            'app_logger': {  # Custom logger
                'handlers': ['console', 'file_handler'],
                'level': 'DEBUG',
                'propagate': False,
            },
        },
    }
    # Apply the configuration
    logging.config.dictConfig(LOGGING_CONFIG)