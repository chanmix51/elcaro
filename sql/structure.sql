CREATE SCHEMA company;
SET search_path TO company, public;

CREATE TABLE department (
    department_id       serial PRIMARY KEY,
    name                varchar NOT NULL,
    parent_id           int REFERENCES department (department_id)
    );

INSERT INTO department (name, parent_id) VALUES
    ('ELCARO Corporation', null),
    ('Headquarters', 1),
    ('Managers', 2),
    ('Accounting', 1),
    ('Technical Direction', 3),
    ('Hotline level II', 5),
    ('Datacenter Skynet', 1),
    ('Operationnal', 7),
    ('Hotline level I', 7),
    ('Managers', 7)
    ;

CREATE TABLE employee (
        employee_id         serial          PRIMARY KEY,
        first_name          varchar         NOT NULL,
        last_name           varchar         NOT NULL,
        birth_date          date            NOT NULL CHECK (age(birth_date) >= '18 years'::interval),
        is_manager          boolean         NOT NULL DEFAULT false,
        day_salary          numeric(7,2)    NOT NULL,
        department_id       integer         NOT NULL REFERENCES department (department_id)
        );

INSERT INTO employee (first_name, last_name, department_id, birth_date, day_salary, is_manager) VALUES
    ('john', 'dupont', 3, '1952-03-21', 20000, true),
    ('alexander', 'gelbetchev', 3, '1960-09-12', 17500, true),
    ('michèle', 'pfizer', 4, '1956-01-07', 15000, true),
    ('lauren', 'galatier', 5, '1969-05-19', 13850, true),
    ('david', 'roneker', 5, '1972-12-02', 9000, true),
    ('ishaam', 'elraouï¯', 6, '1978-06-21', 5600, false),
    ('ester', 'li jih', 6, '1976-08-07', 5300, false),
    ('Ian', 'king', 10, '1964-03-30', 8700, true),
    ('garry', 'carpenter', 9, '1983-09-14', 4500, false),
    ('amid', 'miller', 9, '1981-11-03', 4800, false),
    ('david', 'garadjian', 8, '1985-02-28', 4500, false),
    ('jennifer', 'monacor', 8, '1988-07-11', 4100, true),
    ('patrick', 'cordier', 8, '1980-01-28', 4700, false),
    ('andrew', 'grossein', 8, '1987-10-18', 3900, false)
    ;

