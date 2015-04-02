@echo off

rem -------------------------------------------------------------
rem  Leaps command line bootstrap script for Windows.
rem
rem  @author Xu Tongle <xutongle@gmail.com>
rem  @link http://www.tintsoft.com/
rem  @copyright Copyright (c) 2008 TintSoft LLC
rem  @license http://www.tintsoft.com/license/
rem -------------------------------------------------------------

@setlocal

set LEAPS_PATH=%~dp0

if "%PHP_COMMAND%" == "" set PHP_COMMAND=php.exe

"%PHP_COMMAND%" "%LEAPS_PATH%leaps" %*

@endlocal
