!include "LogicLib.nsh"
!include "nsDialogs.nsh"

; PHP, ses extensions et les DLL VC++ sont livrés localement, sans toucher
; aux composants système ni nécessiter un téléchargement au premier lancement.
!macro customInstall
    DetailPrint "Vérification de PHP, SQLite et des composants Windows..."
    nsExec::ExecToStack '"$INSTDIR\resources\php\php.exe" -c "$INSTDIR\resources\php\php.ini" -d "extension_dir=$INSTDIR\resources\php\ext" "$INSTDIR\resources\php\runtime-check.php"'
    Pop $0
    Pop $1
    ${If} $0 != 0
        MessageBox MB_ICONSTOP|MB_OK "Les composants locaux PHP/SQLite n'ont pas pu démarrer (code $0). Vérifiez Windows 10/11 x64 et votre antivirus, puis relancez le setup. Détail : $1"
        Abort "Vérification du moteur local échouée."
    ${EndIf}
!macroend

; Diaporama de marque affiché uniquement dans l'assistant d'installation.
!ifndef BUILD_UNINSTALLER
    Var YetuInstallerSlideIndex

    !ifndef MUI_WELCOMEPAGE_INTERFACE
        !define MUI_WELCOMEPAGE_INTERFACE
        Var mui.WelcomePage
        Var mui.WelcomePage.Image
        Var mui.WelcomePage.Image.Bitmap
        Var mui.WelcomePage.Title
        Var mui.WelcomePage.Title.Font
        Var mui.WelcomePage.Text
    !endif

    Function YetuInstallerLoadSlide
        ${If} $YetuInstallerSlideIndex == 0
            StrCpy $0 "$PLUGINSDIR\yetu-restaurant.bmp"
        ${ElseIf} $YetuInstallerSlideIndex == 1
            StrCpy $0 "$PLUGINSDIR\yetu-checkout.bmp"
        ${Else}
            StrCpy $0 "$PLUGINSDIR\yetu-hardware.bmp"
        ${EndIf}

        ${NSD_FreeImage} $mui.WelcomePage.Image.Bitmap
        ${NSD_SetStretchedImage} $mui.WelcomePage.Image "$0" $mui.WelcomePage.Image.Bitmap
    FunctionEnd

    Function YetuInstallerRotateSlide
        IntOp $YetuInstallerSlideIndex $YetuInstallerSlideIndex + 1
        ${If} $YetuInstallerSlideIndex >= 3
            StrCpy $YetuInstallerSlideIndex 0
        ${EndIf}

        Call YetuInstallerLoadSlide
    FunctionEnd

    Function YetuInstallerWelcomeShow
        InitPluginsDir
        File "/oname=$PLUGINSDIR\yetu-restaurant.bmp" "${BUILD_RESOURCES_DIR}/installer-slides/yetu-restaurant.bmp"
        File "/oname=$PLUGINSDIR\yetu-checkout.bmp" "${BUILD_RESOURCES_DIR}/installer-slides/yetu-checkout.bmp"
        File "/oname=$PLUGINSDIR\yetu-hardware.bmp" "${BUILD_RESOURCES_DIR}/installer-slides/yetu-hardware.bmp"

        StrCpy $YetuInstallerSlideIndex 0
        Call YetuInstallerLoadSlide
        ${NSD_CreateTimer} YetuInstallerRotateSlide 2800
    FunctionEnd

    Function YetuInstallerWelcomeLeave
        ${NSD_KillTimer} YetuInstallerRotateSlide
    FunctionEnd

    !macro customWelcomePage
        !define MUI_WELCOMEPAGE_TITLE "Bienvenue dans Yeboox Desktop 9.0"
        !define MUI_WELCOMEPAGE_TEXT "L'assistant va installer Yeboox, PHP et une base SQLite privée, sécurisée et prête pour votre activité.$\r$\n$\r$\nAucun serveur de base de données séparé n'est requis.$\r$\n$\r$\nYeboox Desktop est une solution Yetu Technologie.$\r$\n$\r$\nCliquez sur Suivant pour continuer."
        !define MUI_PAGE_CUSTOMFUNCTION_SHOW YetuInstallerWelcomeShow
        !define MUI_PAGE_CUSTOMFUNCTION_LEAVE YetuInstallerWelcomeLeave
        !insertmacro MUI_PAGE_WELCOME
    !macroend
!endif
