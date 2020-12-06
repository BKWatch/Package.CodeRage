::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
::
:: Executes the tool PHP specified on the command line.
::
:: File:        CodeRage/Build/Resource/run-coderage.bat
:: Date:        Sun Jan  4 23:47:34 MST 2009
:: Notice:      This document contains confidential information and
::              trade secrets
::
:: Copyright:   2008 CodeRage
:: Author:      Jonathan Turkanis
:: License:     All rights reserved
::
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

@echo off

set TOOLNAME=%1
set CODERAGE_FILES=Bin CodeRage.php

:: Find php.exe and store it in PHP_CLI
for %%X in (php.exe) do set PHP_CLI=%%~$PATH:X
if "%PHP_CLI%" equ "" (
    echo Can't find PHP command-line executable.
    echo Please add it to your PATH
    goto END
)

:: Make sure PHP_CLI is really the command-line executable
for /F "usebackq" %%X in (`php -r "echo php_sapi_name();"`) do set SAPI=%%X
if "%SAPI%" neq "cli" (
    echo Program '%PHP_CLI%' in PATH is not the PHP command-line executable
    goto END
)

:: Find PHP include_path and store it in INCLUDE_PATH
for /F "usebackq" %%X in (`php -r "echo ini_get('include_path');"`) do set INCLUDE_PATH=%%X

:: Search for CodeRage.php in INCLUDE_PATH, and store the containing directory
:: in CODERAGE
for %%X in (CodeRage.php) do set CODERAGE=%%~dp$INCLUDE_PATH:X

:: Check that each file in CODERAGE_FILES exists in the directory CODERAGE
for %%X in (%CODERAGE_FILES%) do (
    if not exist %CODERAGE%\%%X (
        echo Can't find CodeRage tools in PHP include path
        goto END
    )
)

:: Construct command line
set argv=php %CODERAGE%Bin\%TOOLNAME%.php
:start
if /i _%2 equ _ goto end
set argv=%argv% %2
shift
goto :start
:end

:: Clear environment
set CODERAGE=
set CODERAGE_FILES=
set INCLUDE_PATH=
set PHP_CLI=
set SAPI=
set TOOLNAME=

:: Run command line
%argv%

:END
