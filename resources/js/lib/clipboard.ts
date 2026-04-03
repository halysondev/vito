const fallbackCopyTextToClipboard = async (text: string): Promise<void> => {
  if (typeof document === 'undefined') {
    throw new Error('Clipboard access is not available in this environment.');
  }

  const textArea = document.createElement('textarea');
  textArea.value = text;
  textArea.setAttribute('readonly', '');
  textArea.style.position = 'fixed';
  textArea.style.top = '-9999px';
  textArea.style.opacity = '0';

  document.body.appendChild(textArea);
  textArea.focus();
  textArea.select();

  const copied = document.execCommand('copy');

  document.body.removeChild(textArea);

  if (!copied) {
    throw new Error('Failed to copy to clipboard.');
  }
};

export const copyTextToClipboard = async (text: string): Promise<void> => {
  if (typeof navigator !== 'undefined' && typeof navigator.clipboard?.writeText === 'function') {
    await navigator.clipboard.writeText(text);

    return;
  }

  await fallbackCopyTextToClipboard(text);
};

export const readClipboardText = async (): Promise<string> => {
  if (typeof navigator === 'undefined' || typeof navigator.clipboard?.readText !== 'function') {
    throw new Error('Clipboard paste is not available in this browser.');
  }

  return navigator.clipboard.readText();
};
