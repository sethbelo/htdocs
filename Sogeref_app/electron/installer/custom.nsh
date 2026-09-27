!macro customInstall
  DetailPrint "Vérification du composant Microsoft Visual C++..."
  IfFileExists "$INSTDIR\resources\prerequisites\VC_redist.x64.exe" 0 vc_runtime_done
    ExecWait '"$INSTDIR\resources\prerequisites\VC_redist.x64.exe" /install /quiet /norestart' $0
    DetailPrint "Composant Microsoft Visual C++ : code $0"
  vc_runtime_done:
!macroend
