/*(function () { (function a() { try { (function b(i) { if (('' + (i 
              / i)).length !== 1 || i % 20 === 0) {
                  (function () { }).constructor('debugger')()
              } else {
                  debugger
              }
              b(++i)
          }
          )(0)
      } catch (e) {
          setTimeout(a, 1)
      }
  }
  )()
}
)();
// Disable right-click
document.addEventListener('contextmenu', (e) => 
e.preventDefault()); function ctrlShiftKey(e, keyCode) {
  return e.ctrlKey && e.shiftKey && e.keyCode === 
  keyCode.charCodeAt(0);
}
document.onkeydown = (e) => {
  // Disable F12, Ctrl + Shift + I, Ctrl + Shift + J, Ctrl + U
  if ( event.keyCode === 123 || ctrlShiftKey(e, 'I') || 
    ctrlShiftKey(e, 'J') || ctrlShiftKey(e, 'C') || (e.ctrlKey && 
    e.keyCode === 'U'.charCodeAt(0))
  ) return false;
};
