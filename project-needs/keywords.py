import PyPDF2
import re
from collections import Counter
import nltk
from nltk.corpus import stopwords
from pymorphy2 import MorphAnalyzer
from wordcloud import WordCloud
import matplotlib.pyplot as plt

# Скачиваем стоп-слова для русского языка
nltk.download('stopwords')

# Инициализируем морфологический анализатор
morph = MorphAnalyzer()


def extract_text_from_pdf(pdf_path):
    """Извлекает текст из PDF-файла."""
    with open(pdf_path, 'rb') as file:
        reader = PyPDF2.PdfReader(file)
        text = ''
        for page in reader.pages:
            text += page.extract_text()
        return text


def clean_text(text):
    """Очищает текст: удаляет знаки препинания, приводит к нижнему регистру."""
    # Удаляем всё, кроме букв, цифр и пробелов
    text = re.sub(r'[^а-яА-ЯёЁ\s]', ' ', text.lower())
    return text


def remove_stopwords(text, language='russian'):
    """Удаляет стоп-слова из текста."""
    stop_words = set(stopwords.words(language))
    words = text.split()
    filtered_words = [word for word in words if word not in stop_words and len(word) > 2]  # Исключаем короткие слова
    return ' '.join(filtered_words)


def lemmatize_text(text):
    """Лемматизирует текст: приводит слова к их нормальной форме."""
    words = text.split()
    lemmatized_words = [morph.parse(word)[0].normal_form for word in words]
    return ' '.join(lemmatized_words)


def get_top_words(text, top_n=30):
    """Возвращает топ-N наиболее употребимых слов."""
    words = text.split()
    word_counts = Counter(words)
    return word_counts.most_common(top_n)


def generate_wordcloud(text):
    """Создает облако слов из текста."""
    wordcloud = WordCloud(
        width=800,
        height=400,
        background_color='white',
        font_path=None,  # Укажите путь к шрифту, если нужен другой шрифт
        colormap='viridis',  # Цветовая схема
    ).generate(text)

    # Отображаем облако слов
    plt.figure(figsize=(10, 5))
    plt.imshow(wordcloud, interpolation='bilinear')
    plt.axis('off')
    plt.show()


def main(pdf_path):
    """Основная функция для обработки PDF и вывода топа слов."""
    # Извлекаем текст из PDF
    text = extract_text_from_pdf(pdf_path)
    if not text:
        print("Не удалось извлечь текст из PDF.")
        return

    # Очищаем текст
    cleaned_text = clean_text(text)

    # Удаляем стоп-слова
    filtered_text = remove_stopwords(cleaned_text)

    # Лемматизируем текст
    lemmatized_text = lemmatize_text(filtered_text)

    # Получаем топ-30 слов
    top_words = get_top_words(lemmatized_text)

    # Выводим результат
    print(f"Топ-{len(top_words)} наиболее употребимых слов (без стоп-слов):")
    for i, (word, count) in enumerate(top_words, start=1):
        print(f"{i}. {word}: {count}")

    # Генерируем облако слов
    generate_wordcloud(lemmatized_text)


if __name__ == "__main__":
    pdf_path = "C:/Users/petri/Downloads/SistemAID - Высший пилотаж (4).pdf"  # Укажите путь к вашему PDF
    main(pdf_path)