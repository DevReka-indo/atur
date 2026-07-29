const MENTION_MARKER_PATTERN = /@\[([^\]\r\n]{1,255})\]\(user:([1-9][0-9]*)\)/gu;
const PLAIN_MARKER_PATTERN = /@\[([^\]\r\n]{1,255})\]\(user:[^)\r\n]*\)/gu;
const BLOCK_ELEMENTS = new Set(['DIV', 'P']);

const safeMentionName = (name) => name
    .replace(/[\[\]()\r\n]+/gu, '')
    .replace(/\s+/gu, ' ')
    .trim()
    .slice(0, 100) || 'User';

const createMentionElement = (userId, name) => {
    const safeName = safeMentionName(name);
    const mention = document.createElement('span');
    mention.contentEditable = 'false';
    mention.dataset.mentionUserId = String(userId);
    mention.dataset.mentionName = safeName;
    mention.className = 'mx-0.5 inline-block rounded bg-blue-50 px-1 py-0.5 font-semibold text-blue-700';
    mention.setAttribute('aria-label', `Mention ${safeName}`);
    mention.title = `Mention ${safeName}`;
    mention.textContent = `@${safeName}`;

    return mention;
};

const serializeNode = (node) => {
    if (node.nodeType === Node.TEXT_NODE) {
        return node.textContent ?? '';
    }

    if (node.nodeType !== Node.ELEMENT_NODE) {
        return '';
    }

    if (node.matches('[data-mention-user-id][data-mention-name]')) {
        const userId = Number(node.dataset.mentionUserId);
        const name = safeMentionName(node.dataset.mentionName ?? '');

        return Number.isInteger(userId) && userId > 0
            ? `@[${name}](user:${userId})`
            : node.textContent ?? '';
    }

    if (node.tagName === 'BR') {
        return '\n';
    }

    const content = [...node.childNodes].map(serializeNode).join('');

    return BLOCK_ELEMENTS.has(node.tagName) ? `${content}\n` : content;
};

const serializeNodes = (container, shouldTrim = true) => {
    const content = [...container.childNodes]
        .map(serializeNode)
        .join('')
        .replace(/\n{3,}/gu, '\n\n');

    return shouldTrim ? content.trim() : content;
};

const appendPlainText = (fragment, content) => {
    const safeContent = content.replace(
        PLAIN_MARKER_PATTERN,
        (_, name) => `@${safeMentionName(name)}`,
    );

    safeContent.split('\n').forEach((line, index) => {
        if (index > 0) {
            fragment.append(document.createElement('br'));
        }
        if (line !== '') {
            fragment.append(document.createTextNode(line));
        }
    });
};

const contentFragment = (content) => {
    const fragment = document.createDocumentFragment();
    let offset = 0;

    for (const match of content.matchAll(MENTION_MARKER_PATTERN)) {
        appendPlainText(fragment, content.slice(offset, match.index));
        fragment.append(createMentionElement(match[2], match[1]));
        offset = match.index + match[0].length;
    }

    appendPlainText(fragment, content.slice(offset));

    return fragment;
};

const selectionInside = (editor) => {
    const selection = window.getSelection();

    return selection?.rangeCount
        && selection.isCollapsed
        && editor.contains(selection.anchorNode)
        ? selection
        : null;
};

const adjacentMention = (editor, direction) => {
    const selection = selectionInside(editor);
    if (!selection) {
        return null;
    }

    const node = selection.anchorNode;
    const offset = selection.anchorOffset;
    let candidate = null;

    if (node === editor) {
        candidate = direction === 'backward'
            ? editor.childNodes[offset - 1]
            : editor.childNodes[offset];
    } else if (node.nodeType === Node.TEXT_NODE) {
        if (direction === 'backward' && offset === 0) {
            candidate = node.previousSibling;
        } else if (direction === 'forward' && offset === node.textContent.length) {
            candidate = node.nextSibling;
        }
    }

    return candidate?.nodeType === Node.ELEMENT_NODE
        && candidate.matches('[data-mention-user-id]')
        ? candidate
        : null;
};

const insertAtSelection = (editor, fragment) => {
    const selection = selectionInside(editor);
    if (!selection) {
        editor.append(fragment);
        return;
    }

    const range = selection.getRangeAt(0);
    range.deleteContents();
    const lastNode = fragment.lastChild;
    range.insertNode(fragment);

    if (lastNode) {
        range.setStartAfter(lastNode);
        range.collapse(true);
        selection.removeAllRanges();
        selection.addRange(range);
    }
};

export const renderMentionContent = (element, segments) => {
    element.replaceChildren();

    segments.forEach((segment) => {
        if (segment.type === 'mention') {
            const mention = document.createElement('span');
            mention.className = 'rounded bg-blue-50 px-1 py-0.5 font-semibold text-blue-700';
            mention.textContent = segment.text;
            element.append(mention);

            return;
        }

        element.append(document.createTextNode(segment.text));
    });
};

export const createMentionComposer = (editor, {
    maxLength = 1000,
    placeholder = null,
    fallback = null,
    onInput = () => {},
} = {}) => {
    let isComposing = false;

    const updatePlaceholder = () => {
        const isEmpty = serializeNodes(editor).length === 0;
        placeholder?.classList.toggle('hidden', !isEmpty);
        editor.dataset.empty = isEmpty ? 'true' : 'false';
    };

    const notifyInput = () => {
        updatePlaceholder();
        if (!isComposing) {
            onInput();
        }
    };

    const deserialize = (content = '') => {
        editor.replaceChildren(contentFragment(content));
        updatePlaceholder();
    };

    const serialize = () => serializeNodes(editor);

    const mentionQuery = () => {
        const selection = selectionInside(editor);
        const node = selection?.anchorNode;

        if (!selection || node?.nodeType !== Node.TEXT_NODE) {
            return null;
        }

        const textBeforeCaret = node.textContent.slice(0, selection.anchorOffset);
        const match = textBeforeCaret.match(/(^|\s)@([^\s@[\]()]{0,50})$/u);
        if (!match) {
            return null;
        }

        const range = document.createRange();
        range.setStart(node, textBeforeCaret.lastIndexOf('@'));
        range.setEnd(node, selection.anchorOffset);

        return { range, search: match[2] };
    };

    const insertMention = (member) => {
        const query = mentionQuery();
        if (!query) {
            return false;
        }

        const name = safeMentionName(member.name ?? '');
        const userId = Number(member.id);
        const marker = `@[${name}](user:${userId}) `;
        const currentContent = serializeNodes(editor, false);

        if (!Number.isInteger(userId)
            || userId < 1
            || currentContent.length - query.range.toString().length + marker.length > maxLength) {
            return false;
        }

        query.range.deleteContents();
        const mention = createMentionElement(userId, name);
        const trailingSpace = document.createTextNode(' ');
        query.range.insertNode(trailingSpace);
        query.range.insertNode(mention);
        query.range.setStartAfter(trailingSpace);
        query.range.collapse(true);

        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(query.range);
        editor.focus();
        notifyInput();

        return true;
    };

    const clear = () => deserialize('');

    const setDisabled = (disabled) => {
        editor.contentEditable = disabled ? 'false' : 'true';
        editor.setAttribute('aria-disabled', disabled ? 'true' : 'false');
        editor.classList.toggle('cursor-not-allowed', disabled);
        editor.classList.toggle('opacity-60', disabled);
    };

    editor.addEventListener('paste', (event) => {
        event.preventDefault();
        const plainText = event.clipboardData?.getData('text/plain') ?? '';
        const fragment = document.createDocumentFragment();
        appendPlainText(fragment, plainText);
        insertAtSelection(editor, fragment);
        notifyInput();
    });

    editor.addEventListener('keydown', (event) => {
        const direction = event.key === 'Backspace'
            ? 'backward'
            : event.key === 'Delete' ? 'forward' : null;
        const mention = direction ? adjacentMention(editor, direction) : null;

        if (mention) {
            event.preventDefault();
            mention.remove();
            notifyInput();
        }
    });

    editor.addEventListener('input', notifyInput);
    editor.addEventListener('compositionstart', () => {
        isComposing = true;
    });
    editor.addEventListener('compositionend', () => {
        isComposing = false;
        notifyInput();
    });

    editor.classList.remove('hidden');
    fallback?.classList.add('hidden');
    setDisabled(false);
    deserialize(editor.dataset.initialContent ?? '');

    return {
        clear,
        deserialize,
        focus: () => editor.focus(),
        insertMention,
        mentionQuery,
        serialize,
        setDisabled,
    };
};
